<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventReport;
use App\Entity\Enum\EventStatus;
use App\Entity\Photo;
use App\Form\EventReportType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/event/{id}/report')]
class EventReportController extends AbstractController
{
    private const MAX_PHOTOS = 3;

    #[Route(name: 'app_event_report', methods: ['GET', 'POST'])]
    public function report(
        Event $event,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        string $kernelProjectDir
    ): Response {
        $this->denyAccessUnlessGranted('EVENT_ADD_REPORT', $event);

        $report = $event->getReport() ?? new EventReport();

        $form = $this->createForm(EventReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tempFileIds = $form->get('photos')->getData();
            $tempFileIds = $tempFileIds ? explode(',', $tempFileIds) : [];

            $tempPath = $kernelProjectDir . '/var/uploads/tmp/';
            $finalPath = $kernelProjectDir . '/public/uploads/photos/';
            $filesystem = new Filesystem();

            foreach ($tempFileIds as $tempId) {
                if ($report->getPhotos()->count() >= self::MAX_PHOTOS) {
                    break;
                }
                $tempFilePath = $tempPath . $tempId;
                if ($filesystem->exists($tempFilePath)) {
                    $originalFilename = pathinfo($tempId, PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $newFilename = $safeFilename . '-' . uniqid() . '.jpg';

                    $filesystem->rename($tempFilePath, $finalPath . $newFilename);

                    $photo = new Photo();
                    $photo->setImageName($newFilename);
                    $report->addPhoto($photo);
                }
            }

            if (!$report->getId()) {
                $report->setEvent($event);
                $report->setCreator($this->getUser());
                $event->setReport($report);
            } else {
                $report->setLastEditor($this->getUser());
            }

            $event->setStatus(EventStatus::COMPLETED);

            $entityManager->persist($report);
            $entityManager->persist($event); // <-- ЯВНО УКАЗЫВАЕМ DOCTRINE НА ИЗМЕНЕНИЯ В EVENT
            $entityManager->flush();

            $this->addFlash('success', 'Отчет успешно сохранен! Статус мероприятия обновлен на "Проведено".');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event_report/form.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
            'max_photos' => self::MAX_PHOTOS,
        ]);
    }
}
