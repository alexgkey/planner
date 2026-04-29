<?php

namespace App\Service;

use App\Entity\Department;
use App\Entity\Enum\EventDirection;
use App\Entity\Enum\EventLevel;
use App\Entity\Enum\EventStatus;
use App\Entity\Enum\OnOffLine;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\DepartmentRepository;
use App\Repository\EventRepository;
use Symfony\Component\HttpFoundation\Request;

class EventFilterService
{
    /**
     * @return array{
     *     events: Event[],
     *     can_filter_months: bool,
     *     can_filter_departments: bool,
     *     department_options: Department[],
     *     month_options: array<string, string>,
     *     level_options: EventLevel[],
     *     format_options: OnOffLine[],
     *     direction_options: EventDirection[],
     *     selected_department_ids: int[],
     *     selected_months: string[],
     *     selected_levels: string[],
     *     selected_formats: string[],
     *     selected_directions: string[],
     *     include_undated: bool
     * }
     */
    public function resolveListing(
        Request $request,
        EventRepository $eventRepository,
        DepartmentRepository $departmentRepository,
        ?User $user,
        bool $canViewAny,
        bool $excludeCancelled = false,
    ): array {
        $department = $user?->getEmployee()?->getDepartment();
        $events = $canViewAny
            ? $eventRepository->findActiveByDepartment()
            : ($department ? $eventRepository->findActiveByDepartment($department) : []);

        $monthOptions = $this->buildMonthOptions();
        $defaultMonths = array_keys($monthOptions);
        $selectedMonths = $this->resolveSelectedValues($request, 'months', $defaultMonths);

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

        $levelOptions = EventLevel::cases();
        $formatOptions = OnOffLine::cases();
        $directionOptions = EventDirection::cases();

        $selectedLevels = $this->resolveSelectedValues($request, 'levels', array_map(static fn (EventLevel $case): string => $case->value, $levelOptions));
        $selectedFormats = $this->resolveSelectedValues($request, 'formats', array_map(static fn (OnOffLine $case): string => $case->value, $formatOptions));
        $selectedDirections = $this->resolveSelectedValues($request, 'directions', array_map(static fn (EventDirection $case): string => $case->value, $directionOptions));

        $includeUndated = filter_var(
            $request->query->get('include_undated', '1'),
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        );
        if (null === $includeUndated) {
            $includeUndated = true;
        }

        $events = $this->filterEvents(
            $events,
            $selectedDepartmentIds,
            $selectedMonths,
            $selectedLevels,
            $selectedFormats,
            $selectedDirections,
            $includeUndated,
            $excludeCancelled
        );

        return [
            'events' => $events,
            'can_filter_months' => true,
            'can_filter_departments' => $canViewAny,
            'department_options' => $departmentOptions,
            'month_options' => $monthOptions,
            'level_options' => $levelOptions,
            'format_options' => $formatOptions,
            'direction_options' => $directionOptions,
            'selected_department_ids' => $selectedDepartmentIds,
            'selected_months' => $selectedMonths,
            'selected_levels' => $selectedLevels,
            'selected_formats' => $selectedFormats,
            'selected_directions' => $selectedDirections,
            'include_undated' => $includeUndated,
        ];
    }

    /**
     * @param Event[] $events
     * @param int[] $selectedDepartmentIds
     * @param string[] $selectedMonths
     * @param string[] $selectedLevels
     * @param string[] $selectedFormats
     * @param string[] $selectedDirections
     * @return Event[]
     */
    public function filterEvents(
        array $events,
        array $selectedDepartmentIds,
        array $selectedMonths,
        array $selectedLevels,
        array $selectedFormats,
        array $selectedDirections,
        bool $includeUndated,
        bool $excludeCancelled = false,
    ): array {
        return array_values(array_filter($events, function (Event $event) use (
            $selectedDepartmentIds,
            $selectedMonths,
            $selectedLevels,
            $selectedFormats,
            $selectedDirections,
            $includeUndated,
            $excludeCancelled
        ): bool {
            if ($excludeCancelled && EventStatus::CANCELLED === $event->getStatus()) {
                return false;
            }

            $eventDate = $event->getDate();
            if (null === $eventDate) {
                if (!$includeUndated) {
                    return false;
                }
            } elseif (!in_array($eventDate->format('Y-m'), $selectedMonths, true)) {
                return false;
            }

            if ([] !== $selectedDepartmentIds) {
                $eventDepartmentId = $event->getDepartment()?->getId();
                if (null !== $eventDepartmentId && !in_array($eventDepartmentId, $selectedDepartmentIds, true)) {
                    return false;
                }
            }

            if (!in_array($event->getEventLevel()?->value, $selectedLevels, true)) {
                return false;
            }

            if (!in_array($event->getOnOffLine()?->value, $selectedFormats, true)) {
                return false;
            }

            return in_array($event->getEventDirection()?->value, $selectedDirections, true);
        }));
    }

    /**
     * @return array<string, string>
     */
    public function buildMonthOptions(): array
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

    /**
     * @param string[] $allowedValues
     * @return string[]
     */
    private function resolveSelectedValues(Request $request, string $name, array $allowedValues): array
    {
        $selectedValues = (array) $request->query->all($name);
        $selectedValues = array_values(array_intersect($selectedValues, $allowedValues));

        return [] === $selectedValues ? $allowedValues : $selectedValues;
    }
}
