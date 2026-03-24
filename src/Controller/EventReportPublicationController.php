<?php

namespace App\Controller;

use App\Entity\Enum\EventReportPublicationPlatform;
use App\Entity\Enum\EventReportPublicationStatus;
use App\Entity\EventReportPublication;
use App\Form\EventReportPublicationType;
use App\Repository\EventReportPublicationRepository;
use App\Security\Permissions\AppPermissions;
use App\Security\Voter\EventReportPublicationVoter;
use App\Service\EventReportPublicationManager;
use App\Service\EventReportPublicationPublisher;
use App\Service\EventReportPublicationTextProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/report-publications')]
#[IsGranted(AppPermissions::EVENT_REPORT_VIEW)]
class EventReportPublicationController extends AbstractController
{
    public function __construct(
        #[Autowire('%env(int:EVENT_REPORT_PUBLICATION_VISIBLE_DAYS)%')]
        private readonly int $visibleDays,
        #[Autowire('%env(bool:TELEGRAM_PUBLICATION_ENABLED)%')]
        private readonly bool $telegramEnabled,
        #[Autowire('%env(bool:VK_PUBLICATION_ENABLED)%')]
        private readonly bool $vkEnabled,
    ) {
    }

    #[Route(name: 'app_event_report_publication_index', methods: ['GET'])]
    public function index(EventReportPublicationRepository $repository, EventReportPublicationManager $manager): Response
    {
        $manager->backfillPlatformPublications($this->getUser());
        $publications = $repository->findVisibleQueue($this->visibleDays);

        $cards = [];
        foreach ($publications as $publication) {
            $report = $publication->getEventReport();
            if (null === $report || null === $report->getId()) {
                continue;
            }

            $reportId = $report->getId();
            if (!isset($cards[$reportId])) {
                $cards[$reportId] = [
                    'report' => $report,
                    'event' => $report->getEvent(),
                    'publications' => [],
                ];
            }

            $cards[$reportId]['publications'][$publication->getPlatform()->value] = $publication;
        }

        return $this->render('event_report_publication/index.html.twig', [
            'report_cards' => array_values($cards),
            'visible_days' => $this->visibleDays,
            'platform_enabled' => [
                EventReportPublicationPlatform::TELEGRAM->value => $this->telegramEnabled,
                EventReportPublicationPlatform::VK->value => $this->vkEnabled,
            ],
            'platform_order' => [
                EventReportPublicationPlatform::TELEGRAM,
                EventReportPublicationPlatform::VK,
            ],
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_report_publication_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EventReportPublication $publication, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(EventReportPublicationVoter::MANAGE, $publication);

        $form = $this->createForm(EventReportPublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication
                ->setLastEditedBy($this->getUser())
                ->setStatus($publication->hasPreparedText() ? EventReportPublicationStatus::READY : EventReportPublicationStatus::DRAFT)
                ->setErrorMessage(null);

            $entityManager->flush();
            $this->addFlash('success', 'Текст публикации сохранен.');

            return $this->redirectToRoute('app_event_report_publication_index');
        }

        return $this->render('event_report_publication/edit.html.twig', [
            'publication' => $publication,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/prepare', name: 'app_event_report_publication_prepare', methods: ['POST'])]
    public function prepare(
        Request $request,
        EventReportPublication $publication,
        EventReportPublicationTextProcessor $textProcessor,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(EventReportPublicationVoter::MANAGE, $publication);

        if ($this->isCsrfTokenValid('prepare_publication'.$publication->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $textProcessor->prepare($publication);
                $publication->setLastEditedBy($this->getUser());
                $entityManager->flush();
                $this->addFlash('success', 'Текст публикации подготовлен.');
            } catch (\Throwable $exception) {
                $publication
                    ->setErrorMessage($exception->getMessage())
                    ->setLastEditedBy($this->getUser());
                $entityManager->flush();
                $this->addFlash('danger', 'Не удалось подготовить текст: '.$exception->getMessage());
            }
        }

        return $this->redirectToRoute('app_event_report_publication_index');
    }

    #[Route('/{id}/publish', name: 'app_event_report_publication_publish', methods: ['POST'])]
    public function publish(
        Request $request,
        EventReportPublication $publication,
        EventReportPublicationPublisher $publisher,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(EventReportPublicationVoter::MANAGE, $publication);

        if ($this->isCsrfTokenValid('publish_publication'.$publication->getId(), $request->getPayload()->getString('_token'))) {
            try {
                $publisher->publish($publication, $this->getUser());
                $entityManager->flush();
                $this->addFlash('success', sprintf('Публикация на площадке "%s" выполнена.', $publication->getPlatform()->getLabel()));
            } catch (\Throwable $exception) {
                $publication
                    ->markAsFailed($exception->getMessage())
                    ->setLastEditedBy($this->getUser());
                $entityManager->flush();
                $this->addFlash('danger', 'Не удалось опубликовать материал: '.$exception->getMessage());
            }
        }

        return $this->redirectToRoute('app_event_report_publication_index');
    }

    #[Route('/{id}/skip', name: 'app_event_report_publication_skip', methods: ['POST'])]
    public function skip(
        Request $request,
        EventReportPublication $publication,
        EventReportPublicationManager $manager,
        EntityManagerInterface $entityManager,
    ): Response {
        $this->denyAccessUnlessGranted(EventReportPublicationVoter::MANAGE, $publication);

        if ($this->isCsrfTokenValid('skip_publication'.$publication->getId(), $request->getPayload()->getString('_token'))) {
            $manager->markSkipped($publication, $this->getUser());
            $entityManager->flush();
            $this->addFlash('warning', sprintf('Публикация на площадке "%s" отмечена как "не публиковать".', $publication->getPlatform()->getLabel()));
        }

        return $this->redirectToRoute('app_event_report_publication_index');
    }
}