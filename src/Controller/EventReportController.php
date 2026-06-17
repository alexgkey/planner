<?php

namespace App\Controller;

use App\Audit\AuditAction;
use App\Audit\AuditLogger;
use App\Entity\Event;
use App\Entity\EventReport;
use App\Entity\Enum\EventStatus;
use App\Entity\Photo;
use App\Form\EventReportType;
use App\Security\Voter\EventVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/event/{id}/report')]
class EventReportController extends AbstractController
{
    private const MAX_PHOTOS = 3;

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    #[Route(name: 'app_event_report', methods: ['GET', 'POST'])]
    public function report(
        Event $event,
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        string $kernelProjectDir,
    ): Response {
        $this->denyAccessUnlessGranted(EventVoter::ADD_REPORT, $event);

        $report = $event->getReport() ?? new EventReport();
        $reportAlreadyExists = null !== $report->getId();
        $beforeReportSnapshot = $reportAlreadyExists ? $this->auditLogger->snapshotEventReport($report) : [];
        $beforeEventStatus = $event->getStatus()->value;

        $form = $this->createForm(EventReportType::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tempFileIds = $form->get('photos')->getData();
            $tempFileIds = $tempFileIds ? explode(',', $tempFileIds) : [];
            $beforePhotoIds = $reportAlreadyExists ? ($beforeReportSnapshot['photoIds'] ?? []) : [];

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
            $event->setReportReminderLastSentAt(null);
            $event->setReportReminderSentCount(0);

            $entityManager->persist($report);
            $entityManager->persist($event);
            $entityManager->flush();

            $afterReportSnapshot = $this->auditLogger->snapshotEventReport($report);
            $addedPhotoIds = array_values(array_diff($afterReportSnapshot['photoIds'] ?? [], $beforePhotoIds));
            $scenarioUploaded = !$reportAlreadyExists
                ? null !== $report->getScenarioName()
                : ($beforeReportSnapshot['scenarioName'] ?? null) !== ($afterReportSnapshot['scenarioName'] ?? null);
            $metadata = [
                'report_id' => $report->getId(),
                'photos_added' => count($addedPhotoIds),
                'photo_ids_added' => $addedPhotoIds,
                'scenario_uploaded' => $scenarioUploaded,
            ];
            if ($beforeEventStatus !== $event->getStatus()->value) {
                $metadata['status_change'] = [
                    'from' => $beforeEventStatus,
                    'to' => $event->getStatus()->value,
                ];
            }

            if (!$reportAlreadyExists) {
                $this->auditLogger->logCurrentUser(
                    AuditAction::EVENT_REPORT_CREATED,
                    'event',
                    $event->getId(),
                    $event->getTitle(),
                    [
                        'after' => $afterReportSnapshot,
                    ],
                    $metadata
                );
            } else {
                $changes = $this->auditLogger->buildDiff($beforeReportSnapshot, $afterReportSnapshot);
                if ([] !== $changes || isset($metadata['status_change']) || [] !== $addedPhotoIds || $scenarioUploaded) {
                    $this->auditLogger->logCurrentUser(
                        AuditAction::EVENT_REPORT_UPDATED,
                        'event',
                        $event->getId(),
                        $event->getTitle(),
                        $changes,
                        $metadata
                    );
                }
            }

            if ([] !== $addedPhotoIds) {
                $this->auditLogger->logCurrentUser(
                    AuditAction::EVENT_REPORT_PHOTO_ADDED,
                    'event',
                    $event->getId(),
                    $event->getTitle(),
                    null,
                    [
                        'report_id' => $report->getId(),
                        'photo_ids_added' => $addedPhotoIds,
                        'photo_count_added' => count($addedPhotoIds),
                    ]
                );
            }

            $this->addFlash('success', 'Отчет успешно сохранен. Статус мероприятия обновлен на "Проведено".');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event_report/form.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
            'max_photos' => self::MAX_PHOTOS,
        ]);
    }

    #[Route('/{report_id}/download-scenario', name: 'app_report_download_scenario', methods: ['GET'])]
    public function downloadScenario(int $report_id, EntityManagerInterface $entityManager, string $kernelProjectDir): Response
    {
        $report = $entityManager->getRepository(EventReport::class)->find($report_id);

        if (!$report || !$report->getScenarioName()) {
            throw $this->createNotFoundException('Р¤Р°Р№Р» РЅРµ РЅР°Р№РґРµРЅ.');
        }

        $this->denyAccessUnlessGranted(EventVoter::ADD_REPORT, $report->getEvent());

        $this->auditLogger->logCurrentUser(
            AuditAction::EVENT_REPORT_SCENARIO_DOWNLOADED,
            'event',
            $report->getEvent()?->getId(),
            $report->getEvent()?->getTitle(),
            null,
            [
                'report_id' => $report->getId(),
                'scenario_name' => $report->getScenarioName(),
                'original_scenario_name' => $report->getOriginalScenarioName(),
            ]
        );

        $filePath = $kernelProjectDir . '/public/uploads/scenarios/' . $report->getScenarioName();

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $report->getOriginalScenarioName() ?: $report->getScenarioName()
        );

        return $response;
    }
}


