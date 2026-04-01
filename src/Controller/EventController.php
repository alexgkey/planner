<?php

namespace App\Controller;

use App\Entity\Department;
use App\Entity\Event;
use App\Entity\Enum\EventStatus;
use App\Form\EventType;
use App\Repository\DepartmentRepository;
use App\Repository\EventRepository;
use App\Security\Permissions\AppPermissions;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/events')]
#[IsGranted(AppPermissions::EVENT_VIEW)]
class EventController extends AbstractController
{
    #[Route(name: 'app_event_index', methods: ['GET'])]
    public function index(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository): Response
    {
        $listing = $this->resolveEventListing($request, $eventRepository, $departmentRepository);

        return $this->render('event/index.html.twig', $listing);
    }

    #[Route('/export', name: 'app_event_export', methods: ['GET'])]
    #[IsGranted(AppPermissions::EVENT_ADMIN)]
    public function export(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository): Response
    {
        $listing = $this->resolveEventListing($request, $eventRepository, $departmentRepository);
        $rowsByDepartment = $this->groupEventsForExport($listing['events']);

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
    public function exportPdf(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository): Response
    {
        $listing = $this->resolveEventListing($request, $eventRepository, $departmentRepository);
        $events = $listing['events'];

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
     *     selected_months: string[]
     * }
     */
    private function resolveEventListing(Request $request, EventRepository $eventRepository, DepartmentRepository $departmentRepository): array
    {
        $employee = $this->getUser()?->getEmployee();
        $department = $employee?->getDepartment();
        $canViewAny = $this->canViewAnyEvents();

        $events = $canViewAny
            ? $eventRepository->findActiveByDepartment()
            : ($department ? $eventRepository->findActiveByDepartment($department) : []);

        $monthOptions = $this->buildMonthOptions();
        $defaultMonths = array_keys($monthOptions);
        $selectedMonths = (array) $request->query->all('months');
        $selectedMonths = array_values(array_intersect($selectedMonths, $defaultMonths));
        if ([] === $selectedMonths) {
            $selectedMonths = $defaultMonths;
        }

        $departmentOptions = [];
        $selectedDepartmentIds = [];
        if ($canViewAny) {
            $departmentOptions = $departmentRepository->findActive();
            $defaultDepartmentIds = array_map(
                static fn (Department $department): int => $department->getId(),
                $departmentOptions
            );

            $selectedDepartmentIds = array_map('intval', (array) $request->query->all('departments'));
            $selectedDepartmentIds = array_values(array_intersect($selectedDepartmentIds, $defaultDepartmentIds));
            if ([] === $selectedDepartmentIds) {
                $selectedDepartmentIds = $defaultDepartmentIds;
            }
        }

        $events = $this->filterEvents($events, $selectedDepartmentIds, $selectedMonths);

        return [
            'events' => $events,
            'can_filter_months' => true,
            'can_filter_departments' => $canViewAny,
            'department_options' => $departmentOptions,
            'month_options' => $monthOptions,
            'selected_department_ids' => $selectedDepartmentIds,
            'selected_months' => $selectedMonths,
        ];
    }

    /**
     * @param Event[] $events
     * @param int[] $selectedDepartmentIds
     * @param string[] $selectedMonths
     * @return Event[]
     */
    private function filterEvents(array $events, array $selectedDepartmentIds, array $selectedMonths): array
    {
        return array_values(array_filter($events, function (Event $event) use ($selectedDepartmentIds, $selectedMonths): bool {
            $eventDate = $event->getDate();
            if (null !== $eventDate && !in_array($eventDate->format('Y-m'), $selectedMonths, true)) {
                return false;
            }

            if ([] === $selectedDepartmentIds) {
                return true;
            }

            $eventDepartmentId = $event->getDepartment()?->getId();

            return null === $eventDepartmentId || in_array($eventDepartmentId, $selectedDepartmentIds, true);
        }));
    }

    private function canViewAnyEvents(): bool
    {
        return $this->isGranted(AppPermissions::EVENT_VIEW_ANY)
            || $this->isGranted(AppPermissions::EVENT_MANAGE_ANY)
            || $this->isGranted(AppPermissions::EVENT_ADMIN);
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
            'с %s по %s',
            $this->formatMonthLabel($sortedMonths[0]),
            $this->formatMonthLabel($sortedMonths[array_key_last($sortedMonths)])
        );
    }

    private function formatMonthLabel(string $monthValue): string
    {
        return ((new \DateTimeImmutable($monthValue . '-01'))->format('Y-m-d'))
            ? ((new \DateTimeImmutable($monthValue . '-01'))->format('d.m.Y'))
            : $monthValue;
    }

    /**
     * @return array<string, string>
     */
    private function buildMonthOptions(): array
    {
        $options = [];
        $today = new \DateTimeImmutable('today');
        $currentYear = (int) $today->format('Y');
        $currentMonth = (int) $today->format('n');
        $remainingMonths = 12 - $currentMonth + 1;
        $nextYearMonthsToShow = max(0, 3 - $remainingMonths);

        for ($month = 1; $month <= 12; $month++) {
            $date = new \DateTimeImmutable(sprintf('%d-%02d-01', $currentYear, $month));
            $options[$date->format('Y-m')] = $date->format('F Y');
        }

        for ($month = 1; $month <= $nextYearMonthsToShow; $month++) {
            $date = new \DateTimeImmutable(sprintf('%d-%02d-01', $currentYear + 1, $month));
            $options[$date->format('Y-m')] = $date->format('F Y');
        }

        return $options;
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