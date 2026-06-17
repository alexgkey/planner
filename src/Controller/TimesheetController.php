<?php

namespace App\Controller;

use App\Audit\AuditAction;
use App\Audit\AuditLogger;
use App\Entity\Employee;
use App\Entity\Enum\TimesheetStatus;
use App\Entity\TimesheetEntry;
use App\Entity\User;
use App\Repository\EmployeeRepository;
use App\Repository\TimesheetEntryRepository;
use App\Service\TimesheetAccessService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/timesheet')]
class TimesheetController extends AbstractController
{
    public function __construct(
        private readonly TimesheetAccessService $timesheetAccessService,
        private readonly AuditLogger $auditLogger,
    ) {
    }

    #[Route('/', name: 'app_timesheet_index', methods: ['GET'])]
    public function index(
        Request $request,
        EmployeeRepository $employeeRepository,
        TimesheetEntryRepository $timesheetEntryRepository
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->timesheetAccessService->hasAccess()) {
            throw $this->createAccessDeniedException();
        }

        [$dateFrom, $dateTo] = $this->resolvePeriod($request);
        $employees = $this->resolveEmployeesForUser($user, $employeeRepository);
        $dates = $this->buildDateRange($dateFrom, $dateTo);
        $employeeIds = array_map(static fn (Employee $employee): int => (int) $employee->getId(), $employees);
        $entries = $timesheetEntryRepository->findForEmployeesAndPeriod($employeeIds, $dateFrom, $dateTo);

        $entriesMap = [];
        foreach ($entries as $entry) {
            $employeeId = $entry->getEmployee()?->getId();
            $workDate = $entry->getWorkDate();

            if (null === $employeeId || null === $workDate) {
                continue;
            }

            $entriesMap[$employeeId][$workDate->format('Y-m-d')] = $entry->getStatus();
        }

        $groupedEmployees = [];
        foreach ($employees as $employee) {
            $departmentKey = $employee->getDepartment()?->getId() ?? 0;

            if (!isset($groupedEmployees[$departmentKey])) {
                $groupedEmployees[$departmentKey] = [
                    'department' => $employee->getDepartment(),
                    'employees' => [],
                ];
            }

            $groupedEmployees[$departmentKey]['employees'][] = $employee;
        }

        return $this->render('timesheet/index.html.twig', [
            'grouped_employees' => array_values($groupedEmployees),
            'entries_map' => $entriesMap,
            'dates' => $dates,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'today' => new \DateTimeImmutable('today'),
            'status_choices' => TimesheetStatus::choices(),
            'can_manage_any' => $this->timesheetAccessService->canManageAllDepartments(),
            'show_department_scope' => $this->timesheetAccessService->canViewOwnDepartment(),
            'show_all_scope' => $this->timesheetAccessService->canViewAllDepartments(),
            'access_service' => $this->timesheetAccessService,
        ]);
    }

    #[Route('/update', name: 'app_timesheet_update', methods: ['POST'])]
    public function update(
        Request $request,
        EmployeeRepository $employeeRepository,
        TimesheetEntryRepository $timesheetEntryRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof User || !$this->timesheetAccessService->hasAccess()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('timesheet_update', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Некорректный CSRF-токен.');
        }

        $employeeId = (int) $request->request->get('employee_id');
        $dateString = trim((string) $request->request->get('work_date'));
        $statusValue = trim((string) $request->request->get('status'));
        $dateFrom = trim((string) $request->request->get('date_from'));
        $dateTo = trim((string) $request->request->get('date_to'));

        $employee = $employeeRepository->find($employeeId);
        if (!$employee instanceof Employee || !$employee->isActive()) {
            throw $this->createNotFoundException('Сотрудник не найден.');
        }

        $workDate = \DateTimeImmutable::createFromFormat('Y-m-d', $dateString);
        if (false === $workDate) {
            $this->addFlash('danger', 'Некорректная дата табеля.');

            return $this->redirectToRoute('app_timesheet_index', [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]);
        }

        if (!$this->timesheetAccessService->canEditDate($user, $employee, $workDate)) {
            throw $this->createAccessDeniedException('Недостаточно прав для изменения этой записи табеля.');
        }

        $entry = $timesheetEntryRepository->findOneBy([
            'employee' => $employee,
            'workDate' => $workDate,
        ]);
        $beforeSnapshot = $entry instanceof TimesheetEntry
            ? $this->auditLogger->snapshotTimesheetEntry($entry)
            : [];

        if ('' === $statusValue) {
            if ($entry instanceof TimesheetEntry) {
                $subjectLabel = $this->buildTimesheetSubjectLabel($employee, $workDate);
                $entityManager->remove($entry);
                $entityManager->flush();

                $this->auditLogger->logCurrentUser(
                    AuditAction::TIMESHEET_ENTRY_CLEARED,
                    'timesheet_entry',
                    $entry->getId(),
                    $subjectLabel,
                    [
                        'before' => $beforeSnapshot,
                    ],
                    $this->buildTimesheetAuditMetadata($employee, $workDate, $dateFrom, $dateTo)
                );
            }

            return $this->redirectToRoute('app_timesheet_index', [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]);
        }

        $status = TimesheetStatus::tryFrom($statusValue);
        if (null === $status) {
            $this->addFlash('danger', 'Выбрано недопустимое значение табеля.');

            return $this->redirectToRoute('app_timesheet_index', [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]);
        }

        if (!$entry instanceof TimesheetEntry) {
            $entry = (new TimesheetEntry())
                ->setEmployee($employee)
                ->setWorkDate($workDate);
            $entityManager->persist($entry);
        }

        $entry->setStatus($status);
        $entityManager->flush();

        $afterSnapshot = $this->auditLogger->snapshotTimesheetEntry($entry);
        $subjectLabel = $this->buildTimesheetSubjectLabel($employee, $workDate);
        $metadata = $this->buildTimesheetAuditMetadata($employee, $workDate, $dateFrom, $dateTo);

        if ([] === $beforeSnapshot) {
            $this->auditLogger->logCurrentUser(
                AuditAction::TIMESHEET_ENTRY_CREATED,
                'timesheet_entry',
                $entry->getId(),
                $subjectLabel,
                [
                    'after' => $afterSnapshot,
                ],
                $metadata
            );
        } else {
            $changes = $this->auditLogger->buildDiff($beforeSnapshot, $afterSnapshot);
            if ([] !== $changes) {
                $this->auditLogger->logCurrentUser(
                    AuditAction::TIMESHEET_ENTRY_UPDATED,
                    'timesheet_entry',
                    $entry->getId(),
                    $subjectLabel,
                    $changes,
                    $metadata
                );
            }
        }

        return $this->redirectToRoute('app_timesheet_index', [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);
    }

    /**
     * @return Employee[]
     */
    private function resolveEmployeesForUser(User $user, EmployeeRepository $employeeRepository): array
    {
        if ($this->timesheetAccessService->canViewAllDepartments()) {
            return $employeeRepository->findActiveForTimesheet();
        }

        if ($this->timesheetAccessService->canViewOwnDepartment()) {
            $department = $user->getEmployee()?->getDepartment();
            if (null !== $department) {
                return $employeeRepository->findActiveForTimesheet($department);
            }
        }

        $currentEmployee = $user->getEmployee();

        return null !== $currentEmployee && $currentEmployee->isActive()
            ? [$currentEmployee]
            : [];
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function resolvePeriod(Request $request): array
    {
        $today = new \DateTimeImmutable('today');
        $defaultFrom = $today->modify('-7 days');
        $defaultTo = $today->modify('+7 days');

        $dateFrom = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $request->query->get('date_from')) ?: $defaultFrom;
        $dateTo = \DateTimeImmutable::createFromFormat('Y-m-d', (string) $request->query->get('date_to')) ?: $defaultTo;

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [$dateFrom->setTime(0, 0), $dateTo->setTime(0, 0)];
    }

    /**
     * @return \DateTimeImmutable[]
     */
    private function buildDateRange(\DateTimeImmutable $dateFrom, \DateTimeImmutable $dateTo): array
    {
        $dates = [];
        for ($date = $dateFrom; $date <= $dateTo; $date = $date->modify('+1 day')) {
            $dates[] = $date;
        }

        return $dates;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTimesheetAuditMetadata(Employee $employee, \DateTimeImmutable $workDate, string $dateFrom, string $dateTo): array
    {
        return [
            'employee_id' => $employee->getId(),
            'employee_fio' => $employee->getFio(),
            'department_id' => $employee->getDepartment()?->getId(),
            'department_title' => $employee->getDepartment()?->getTitle(),
            'work_date' => $workDate->format('Y-m-d'),
            'visible_period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
        ];
    }

    private function buildTimesheetSubjectLabel(Employee $employee, \DateTimeImmutable $workDate): string
    {
        return sprintf('%s %s', $employee->getFio(), $workDate->format('Y-m-d'));
    }
}
