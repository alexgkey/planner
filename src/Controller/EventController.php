<?php

namespace App\Controller;

use App\Entity\Department;
use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Form\EventType;
use App\Repository\DepartmentRepository;
use App\Repository\EventRepository;
use App\Security\Permissions\AppPermissions;
use App\Service\EventAnalyticsService;
use App\Service\EventFilterService;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events')]
#[IsGranted(AppPermissions::EVENT_VIEW)]
class EventController extends AbstractController
{
    #[Route(name: 'app_event_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository, EventFilterService $eventFilterService): Response
    {
        $listing = $this->resolveEventListing($request, $eventRepository, $departmentRepository, $eventFilterService);

        return $this->render('event/index.html.twig', $listing);
    }

    #[Route('/export', name: 'app_event_export', methods: ['GET'])]
    #[IsGranted(AppPermissions::EVENT_ADMIN)]
    public function export(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository, EventFilterService $eventFilterService): Response
    {
        $listing = $this->resolveEventListing($request, $eventRepository, $departmentRepository, $eventFilterService);
        $events = $this->resolveSelectedExportEvents($request, $listing['events']);
        $rowsByDepartment = $this->groupEventsForExport($this->filterExportableEvents($events));

        $content = $this->renderView('event/export.xls.twig', [
            'rows_by_department' => $rowsByDepartment,
            'generated_at' => new \DateTimeImmutable(),
        ]);

        $filename = sprintf('events-export-%s.xls', (new \DateTimeImmutable())->format('Y-m-d-His'));

        return new Response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    #[Route('/export/pdf', name: 'app_event_export_pdf', methods: ['GET'])]
    public function exportPdf(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository, EventFilterService $eventFilterService): Response
    {
        $listing = $this->resolveEventListing($request, $eventRepository, $departmentRepository, $eventFilterService);
        $events = $this->filterExportableEvents($this->resolveSelectedExportEvents($request, $listing['events']));
        $this->sortEventsForPdfExport($events);

        $headerDepartment = $this->resolvePdfDepartmentLabel($listing['department_options'], $listing['selected_department_ids']);
        $signatureDepartment = $this->resolvePdfSignatureDepartmentLabel($headerDepartment);
        $periodLabel = $this->buildSelectedPeriodLabel($listing['month_options'], $listing['selected_months']);
        $generatedAt = new \DateTimeImmutable();

        $html = $this->renderView('event/export.pdf.twig', [
            'events' => $events,
            'header_department' => $headerDepartment,
            'signature_department' => $signatureDepartment,
            'period_label' => $periodLabel,
            'generated_at' => $generatedAt,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = sprintf('events-plan-%s.pdf', $generatedAt->format('Y-m-d-His'));

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    #[Route('/export/reports/pdf', name: 'app_event_export_reports_pdf', methods: ['GET'])]
    public function exportReportsPdf(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository, EventFilterService $eventFilterService): Response
    {
        $listing = $this->resolveEventListing($request, $eventRepository, $departmentRepository, $eventFilterService);
        $events = $this->filterExportableEvents($this->resolveSelectedExportEvents($request, $listing['events']));
        $this->sortEventsForPdfExport($events);

        $headerDepartment = $this->resolvePdfDepartmentLabel($listing['department_options'], $listing['selected_department_ids']);
        $signatureDepartment = $this->resolvePdfSignatureDepartmentLabel($headerDepartment);
        $periodLabel = $this->buildSelectedPeriodLabel($listing['month_options'], $listing['selected_months']);
        $generatedAt = new \DateTimeImmutable();

        $html = $this->renderView('event/export_reports.pdf.twig', [
            'events' => $events,
            'header_department' => $headerDepartment,
            'signature_department' => $signatureDepartment,
            'period_label' => $periodLabel,
            'generated_at' => $generatedAt,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = sprintf('events-reports-%s.pdf', $generatedAt->format('Y-m-d-His'));

        return new Response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    #[Route('/export/photos', name: 'app_event_export_photos', methods: ['GET'])]
    public function exportPhotos(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository, EventFilterService $eventFilterService): Response
    {
        $listing = $this->resolveEventListing($request, $eventRepository, $departmentRepository, $eventFilterService);
        $events = $this->filterExportableEvents($this->resolveSelectedExportEvents($request, $listing['events']));

        $photoFiles = $this->collectReportPhotoFiles($events);
        if ([] === $photoFiles) {
            $this->addFlash('warning', 'В выбранных мероприятиях нет фотографий отчетов для экспорта.');

            return $this->redirectToRoute('app_event_index', $request->query->all());
        }

        $zipPath = $this->createStoredZipArchive($photoFiles);
        $filename = sprintf('event-photos-%s.zip', (new \DateTimeImmutable())->format('Y-m-d-His'));
        $response = new BinaryFileResponse($zipPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Type', 'application/zip');
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/analytics', name: 'app_event_analytics', methods: ['GET'])]
    #[IsGranted(AppPermissions::EVENT_ADMIN)]
    public function analytics(
        Request $request,
        EventRepository $eventRepository,
        DepartmentRepository $departmentRepository,
        EventFilterService $eventFilterService,
        EventAnalyticsService $eventAnalyticsService,
    ): Response {
        $listing = $this->resolveEventListing($request, $eventRepository, $departmentRepository, $eventFilterService, true);
        $analytics = $eventAnalyticsService->build($listing['events']);

        return $this->render('event/analytics.html.twig', $listing + [
            'analytics' => $analytics,
            'metric_labels' => $eventAnalyticsService->getReportMetricLabels(),
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->canDuplicateEvent()) {
            throw $this->createAccessDeniedException('Недостаточно прав для создания мероприятия.');
        }

        $user = $this->getUser();
        $department = $user?->getEmployee()?->getDepartment();
        if (null === $department) {
            throw $this->createAccessDeniedException('Для создания мероприятия у пользователя должен быть указан отдел.');
        }

        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setCreator($user);
            $event->setDepartment($department);
            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
            'page_title' => 'Новое мероприятие',
            'page_hint' => null,
        ]);
    }

    #[Route('/{id}/duplicate', name: 'app_event_duplicate', methods: ['GET', 'POST'])]
    public function duplicate(Request $request, Event $sourceEvent, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(AppPermissions::EVENT_VIEW, $sourceEvent);

        if (!$this->canDuplicateEvent()) {
            throw $this->createAccessDeniedException('Недостаточно прав для создания копии мероприятия.');
        }

        $targetDepartment = $this->resolveDuplicateDepartment($sourceEvent);
        if (null === $targetDepartment) {
            throw $this->createAccessDeniedException('Не удалось определить отдел для копии мероприятия.');
        }

        $event = new Event();
        $this->copyEventData($sourceEvent, $event);
        $event->setDepartment($targetDepartment);

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $event->setCreator($this->getUser());
            $event->setDepartment($targetDepartment);
            $entityManager->persist($event);
            $entityManager->flush();

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
            'page_title' => 'Создание копии мероприятия',
            'page_hint' => sprintf('Исходное мероприятие: "%s".', $sourceEvent->getTitle()),
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        $this->denyAccessUnlessGranted(AppPermissions::EVENT_VIEW, $event);

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'can_duplicate' => $this->canDuplicateEvent(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted(AppPermissions::EVENT_MANAGE_ANY, $event)) {
            $this->denyAccessUnlessGranted(AppPermissions::EVENT_MANAGE_OWN, $event);
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/cancel', name: 'app_event_cancel', methods: ['POST'])]
    public function cancel(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted(AppPermissions::EVENT_MANAGE_ANY, $event)) {
            $this->denyAccessUnlessGranted(AppPermissions::EVENT_MANAGE_OWN, $event);
        }

        if ($this->isCsrfTokenValid('cancel'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $event->setStatus(EventStatus::CANCELLED);
            $entityManager->flush();
            $this->addFlash('warning', 'Мероприятие было отменено.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/restore', name: 'app_event_restore', methods: ['POST'])]
    #[IsGranted(AppPermissions::EVENT_ADMIN)]
    public function restore(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('restore'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $event->setStatus(EventStatus::PLANNED);
            $entityManager->flush();
            $this->addFlash('success', 'Мероприятие было восстановлено.');
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted(AppPermissions::EVENT_MANAGE_ANY, $event)) {
            $this->denyAccessUnlessGranted(AppPermissions::EVENT_MANAGE_OWN, $event);
        }

        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->getPayload()->getString('_token'))) {
            $event->setIsActive(false);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_event_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * @return array{
     *     events: Event[],
     *     can_filter_months: bool,
     *     can_filter_departments: bool,
     *     department_options: Department[],
     *     month_options: array<string, string>,
     *     selected_department_ids: int[],
     *     selected_months: string[],
     *     include_undated: bool
     * }
     */
    private function resolveEventListing(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository, EventFilterService $eventFilterService, bool $excludeCancelled = false): array
    {
        return $eventFilterService->resolveListing(
            $request,
            $eventRepository,
            $departmentRepository,
            $this->getUser(),
            $this->canViewAnyEvents(),
            $excludeCancelled
        );
    }

    private function canViewAnyEvents(): bool
    {
        return $this->isGranted(AppPermissions::EVENT_VIEW_ANY)
            || $this->isGranted(AppPermissions::EVENT_MANAGE_ANY)
            || $this->isGranted(AppPermissions::EVENT_ADMIN);
    }

    /**
     * @param Event[] $events
     * @return Event[]
     */
    private function resolveSelectedExportEvents(Request $request, array $events): array
    {
        $selectedIds = [];
        foreach ($request->query->all() as $key => $value) {
            if ('event_ids' !== $key && 'event_ids[]' !== $key && !str_starts_with((string) $key, 'event_ids[')) {
                continue;
            }

            foreach ((array) $value as $selectedId) {
                $selectedIds[] = (int) $selectedId;
            }
        }

        $selectedIds = array_values(array_unique(array_filter($selectedIds, static fn (int $id): bool => $id > 0)));

        if ([] === $selectedIds) {
            return $events;
        }

        $selectedIdMap = array_fill_keys($selectedIds, true);

        return array_values(array_filter(
            $events,
            static fn (Event $event): bool => null !== $event->getId() && isset($selectedIdMap[$event->getId()])
        ));
    }

    /**
     * @param Event[] $events
     * @return array<int, array{path: string, archive_name: string}>
     */
    private function collectReportPhotoFiles(array $events): array
    {
        $photoFiles = [];
        $usedArchiveNames = [];
        $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/photos';

        foreach ($events as $event) {
            $report = $event->getReport();
            if (null === $report || !$report->isActive()) {
                continue;
            }

            foreach ($report->getPhotos() as $photo) {
                $imageName = $photo->getImageName();
                if (null === $imageName || '' === trim($imageName)) {
                    continue;
                }

                $path = $uploadDirectory . '/' . $imageName;
                if (!is_file($path) || !is_readable($path)) {
                    continue;
                }

                $archiveName = $this->buildPhotoArchiveName($event, $photo->getId(), $imageName);
                $baseArchiveName = $archiveName;
                $counter = 2;
                while (isset($usedArchiveNames[$archiveName])) {
                    $archiveName = $this->appendFilenameSuffix($baseArchiveName, $counter);
                    ++$counter;
                }

                $usedArchiveNames[$archiveName] = true;
                $photoFiles[] = [
                    'path' => $path,
                    'archive_name' => $archiveName,
                ];
            }
        }

        return $photoFiles;
    }

    private function buildPhotoArchiveName(Event $event, ?int $photoId, string $imageName): string
    {
        $eventId = $event->getId() ?? 0;
        $eventDate = $event->getDate()?->format('Y-m-d') ?? 'без-даты';
        $eventTitle = $this->sanitizeArchivePathPart($event->getTitle() ?? 'мероприятие');
        $photoPrefix = null !== $photoId ? sprintf('photo-%d', $photoId) : 'photo';

        return sprintf(
            '%s_%d_%s/%s_%s',
            $eventDate,
            $eventId,
            $eventTitle,
            $photoPrefix,
            basename($imageName)
        );
    }

    private function sanitizeArchivePathPart(string $value): string
    {
        $value = preg_replace('/[^\p{L}\p{N}\-_ ]+/u', '', $value) ?? '';
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return '' === $value ? 'без-названия' : mb_substr($value, 0, 80);
    }

    private function appendFilenameSuffix(string $path, int $suffix): string
    {
        $directory = pathinfo($path, PATHINFO_DIRNAME);
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $suffixedFilename = sprintf('%s-%d', $filename, $suffix);

        if ('' !== $extension) {
            $suffixedFilename .= '.' . $extension;
        }

        return '.' === $directory ? $suffixedFilename : $directory . '/' . $suffixedFilename;
    }

    /**
     * Creates a ZIP archive without ext-zip. Files are stored without compression,
     * which is suitable for already-compressed JPEG/PNG report photos.
     *
     * @param array<int, array{path: string, archive_name: string}> $files
     */
    private function createStoredZipArchive(array $files): string
    {
        $zipPath = tempnam(sys_get_temp_dir(), 'event-photos-');
        if (false === $zipPath) {
            throw new \RuntimeException('Не удалось создать временный файл для архива фотографий.');
        }

        $handle = fopen($zipPath, 'wb');
        if (false === $handle) {
            @unlink($zipPath);
            throw new \RuntimeException('Не удалось открыть временный файл для архива фотографий.');
        }

        $centralDirectory = [];
        foreach ($files as $file) {
            $path = $file['path'];
            $archiveName = str_replace('\\', '/', $file['archive_name']);
            $fileSize = filesize($path);
            if (false === $fileSize) {
                continue;
            }

            $crc = (int) hexdec(hash_file('crc32b', $path));
            $offset = ftell($handle);
            [$dosTime, $dosDate] = $this->buildDosDateTime(filemtime($path) ?: time());

            fwrite($handle, pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0x0800,
                0,
                $dosTime,
                $dosDate,
                $crc,
                $fileSize,
                $fileSize,
                strlen($archiveName),
                0
            ));
            fwrite($handle, $archiveName);

            $source = fopen($path, 'rb');
            if (false === $source) {
                continue;
            }

            stream_copy_to_stream($source, $handle);
            fclose($source);

            $centralDirectory[] = [
                'archive_name' => $archiveName,
                'crc' => $crc,
                'size' => $fileSize,
                'dos_time' => $dosTime,
                'dos_date' => $dosDate,
                'offset' => $offset,
            ];
        }

        $centralDirectoryOffset = ftell($handle);
        foreach ($centralDirectory as $entry) {
            fwrite($handle, pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0x0800,
                0,
                $entry['dos_time'],
                $entry['dos_date'],
                $entry['crc'],
                $entry['size'],
                $entry['size'],
                strlen($entry['archive_name']),
                0,
                0,
                0,
                0,
                0,
                $entry['offset']
            ));
            fwrite($handle, $entry['archive_name']);
        }

        $centralDirectorySize = ftell($handle) - $centralDirectoryOffset;
        fwrite($handle, pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            count($centralDirectory),
            count($centralDirectory),
            $centralDirectorySize,
            $centralDirectoryOffset,
            0
        ));

        fclose($handle);

        return $zipPath;
    }

    /**
     * @return array{int, int}
     */
    private function buildDosDateTime(int $timestamp): array
    {
        $parts = getdate($timestamp);
        $year = max(1980, (int) $parts['year']);
        $dosTime = ((int) $parts['hours'] << 11) | ((int) $parts['minutes'] << 5) | ((int) floor((int) $parts['seconds'] / 2));
        $dosDate = (($year - 1980) << 9) | ((int) $parts['mon'] << 5) | (int) $parts['mday'];

        return [$dosTime, $dosDate];
    }

    /**
     * @param Event[] $events
     * @return array<string, Event[]>
     */
    private function groupEventsForExport(array $events): array
    {
        usort($events, function (Event $left, Event $right): int {
            $leftDepartment = mb_strtolower($left->getDepartment()?->getTitle() ?? 'Без подразделения');
            $rightDepartment = mb_strtolower($right->getDepartment()?->getTitle() ?? 'Без подразделения');

            $departmentComparison = $leftDepartment <=> $rightDepartment;
            if (0 !== $departmentComparison) {
                return $departmentComparison;
            }

            $leftDate = $left->getDate()?->format('Y-m-d') ?? '9999-12-31';
            $rightDate = $right->getDate()?->format('Y-m-d') ?? '9999-12-31';
            $dateComparison = $leftDate <=> $rightDate;
            if (0 !== $dateComparison) {
                return $dateComparison;
            }

            $leftTime = $left->getTime()?->format('H:i') ?? '99:99';
            $rightTime = $right->getTime()?->format('H:i') ?? '99:99';
            $timeComparison = $leftTime <=> $rightTime;
            if (0 !== $timeComparison) {
                return $timeComparison;
            }

            return strcmp((string) $left->getTitle(), (string) $right->getTitle());
        });

        $rowsByDepartment = [];
        foreach ($events as $event) {
            $departmentTitle = $event->getDepartment()?->getTitle() ?? 'Без подразделения';
            $rowsByDepartment[$departmentTitle][] = $event;
        }

        return $rowsByDepartment;
    }

    /**
     * @param Event[] $events
     */
    private function sortEventsForPdfExport(array &$events): void
    {
        usort($events, function (Event $left, Event $right): int {
            $leftDate = $left->getDate()?->format('Y-m-d') ?? '9999-12-31';
            $rightDate = $right->getDate()?->format('Y-m-d') ?? '9999-12-31';
            $dateComparison = $leftDate <=> $rightDate;
            if (0 !== $dateComparison) {
                return $dateComparison;
            }

            $leftTime = $left->getTime()?->format('H:i') ?? '99:99';
            $rightTime = $right->getTime()?->format('H:i') ?? '99:99';
            $timeComparison = $leftTime <=> $rightTime;
            if (0 !== $timeComparison) {
                return $timeComparison;
            }

            return strcmp((string) $left->getTitle(), (string) $right->getTitle());
        });
    }

    /**
     * @param Department[] $departmentOptions
     * @param int[] $selectedDepartmentIds
     */
    private function resolvePdfDepartmentLabel(array $departmentOptions, array $selectedDepartmentIds): string
    {
        if ([] === $selectedDepartmentIds) {
            return $this->getUser()?->getEmployee()?->getDepartment()?->getTitle() ?? 'Все подразделения';
        }

        $selectedDepartments = array_values(array_filter(
            $departmentOptions,
            static fn (Department $department): bool => in_array($department->getId(), $selectedDepartmentIds, true)
        ));

        if (1 === count($selectedDepartments)) {
            return $selectedDepartments[0]->getTitle() ?? 'Подразделение';
        }

        return 'Все подразделения';
    }

    private function resolvePdfSignatureDepartmentLabel(string $headerDepartment): string
    {
        return 'Все подразделения' === $headerDepartment ? '' : $headerDepartment;
    }


    /**
     * @param Event[] $events
     * @return Event[]
     */
    private function filterExportableEvents(array $events): array
    {
        return array_values(array_filter($events, static function (Event $event): bool {
            return $event->isActive() && EventStatus::CANCELLED !== $event->getStatus();
        }));
    }

    /**
     * @param array<string, string> $monthOptions
     * @param string[] $selectedMonths
     */
    private function buildSelectedPeriodLabel(array $monthOptions, array $selectedMonths): string
    {
        if ([] === $selectedMonths) {
            return 'не выбран';
        }

        if (1 === count($selectedMonths)) {
            return $this->formatMonthLabel($selectedMonths[0]);
        }

        $sortedMonths = $selectedMonths;
        sort($sortedMonths);

        return sprintf(
            '%s - %s',
            $this->formatMonthLabel($sortedMonths[0]),
            $this->formatMonthLabel($sortedMonths[array_key_last($sortedMonths)])
        );
    }

    private function formatMonthLabel(string $monthValue): string
    {
        try {
            $date = new \DateTimeImmutable($monthValue . '-01');
        } catch (\Exception) {
            return $monthValue;
        }

        $months = [
            1 => 'январь',
            2 => 'февраль',
            3 => 'март',
            4 => 'апрель',
            5 => 'май',
            6 => 'июнь',
            7 => 'июль',
            8 => 'август',
            9 => 'сентябрь',
            10 => 'октябрь',
            11 => 'ноябрь',
            12 => 'декабрь',
        ];

        $monthNumber = (int) $date->format('n');

        return sprintf('%s %s', $months[$monthNumber] ?? $monthValue, $date->format('Y'));
    }

    private function canDuplicateEvent(): bool
    {
        return $this->isGranted(AppPermissions::EVENT_ADMIN)
            || $this->isGranted(AppPermissions::EVENT_MANAGE_ANY)
            || $this->isGranted(AppPermissions::EVENT_MANAGE_OWN);
    }

    private function resolveDuplicateDepartment(Event $sourceEvent): ?Department
    {
        if ($this->isGranted(AppPermissions::EVENT_ADMIN) || $this->isGranted(AppPermissions::EVENT_MANAGE_ANY)) {
            return $sourceEvent->getDepartment() ?? $this->getUser()?->getEmployee()?->getDepartment();
        }

        return $this->getUser()?->getEmployee()?->getDepartment();
    }

    private function copyEventData(Event $sourceEvent, Event $targetEvent): void
    {
        $targetEvent
            ->setTitle($sourceEvent->getTitle() ?? '')
            ->setVenue($sourceEvent->getVenue())
            ->setEventLevel($sourceEvent->getEventLevel())
            ->setOnOffLine($sourceEvent->getOnOffLine())
            ->setEventDirection($sourceEvent->getEventDirection())
            ->setEventAccessibility($sourceEvent->getEventAccessibility())
            ->setTargetAudience($sourceEvent->getTargetAudience())
            ->setInteraction($sourceEvent->getInteraction())
            ->setResponsible($sourceEvent->getResponsible() ?? '')
            ->setPlannedVisitors($sourceEvent->getPlannedVisitors())
            ->setNote($sourceEvent->getNote())
            ->setTime($sourceEvent->getTime())
            ->setDate(null)
            ->setStatus(EventStatus::PLANNED);
    }
}
