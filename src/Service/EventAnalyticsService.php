<?php

namespace App\Service;

use App\Entity\Enum\EventStatus;
use App\Entity\Event;
use App\Entity\EventReport;

class EventAnalyticsService
{
    /**
     * @return array<string, string>
     */
    public function getReportMetricLabels(): array
    {
        return [
            'visitorsCount' => 'Посетители/просмотры',
            'participantsCount' => 'Участники',
            'disabledVisitorsCount' => 'Инвалиды и ОВЗ',
            'seniorsVisitorsCount' => 'Пенсионеры',
            'adultsVisitorsCount' => 'Взрослые',
            'youthVisitorsCount' => 'Молодежь',
            'childrenVisitorsCount' => 'Дети',
            'mixedAudienceCount' => 'Смешанная аудитория',
            'childrenAtRiskCount' => 'Дети на учете',
            'smoParticipantsCount' => 'Участники СВО',
            'smoFamiliesCount' => 'Семьи участников СВО',
            'youngFamiliesCount' => 'Молодые семьи',
            'volunteersCount' => 'Волонтеры',
        ];
    }

    /**
     * @param Event[] $events
     * @return array{
     *     summary: array<string, int|float>,
     *     distributions: array<string, array<string, int>>,
     *     departments: array<int|string, array{title: string, events: Event[], totals: array<string, int>, total_events: int, completed_events: int, reported_events: int}>,
     *     totals: array<string, int>
     * }
     */
    public function build(array $events): array
    {
        $totalEvents = count($events);
        $completedEvents = 0;
        $reportedEvents = 0;
        $departments = [];
        $totals = $this->emptyMetricTotals();

        $distributions = [
            'levels' => [],
            'formats' => [],
            'directions' => [],
            'accessibility' => [],
            'audiences' => [],
        ];

        foreach ($events as $event) {
            if (EventStatus::COMPLETED === $event->getStatus()) {
                ++$completedEvents;
            }

            $report = $event->getReport();
            if (null !== $report) {
                ++$reportedEvents;
            }

            $this->incrementDistribution($distributions['levels'], $event->getEventLevel()?->getLabel());
            $this->incrementDistribution($distributions['formats'], $event->getOnOffLine()?->getLabel());
            $this->incrementDistribution($distributions['directions'], $event->getEventDirection()?->getLabel());
            $this->incrementDistribution($distributions['accessibility'], $event->getEventAccessibility()?->getLabel());
            $this->incrementDistribution($distributions['audiences'], $event->getTargetAudience()?->getLabel());

            $department = $event->getDepartment();
            $departmentKey = $department?->getId() ?? 'none';
            if (!isset($departments[$departmentKey])) {
                $departments[$departmentKey] = [
                    'title' => $department?->getTitle() ?? 'Без подразделения',
                    'events' => [],
                    'totals' => $this->emptyMetricTotals(),
                    'total_events' => 0,
                    'completed_events' => 0,
                    'reported_events' => 0,
                ];
            }

            $departments[$departmentKey]['events'][] = $event;
            ++$departments[$departmentKey]['total_events'];

            if (EventStatus::COMPLETED === $event->getStatus()) {
                ++$departments[$departmentKey]['completed_events'];
            }

            if (null !== $report) {
                ++$departments[$departmentKey]['reported_events'];
                foreach ($this->readReportMetrics($report) as $metric => $value) {
                    $departments[$departmentKey]['totals'][$metric] += $value;
                    $totals[$metric] += $value;
                }
            }
        }

        foreach ($departments as &$departmentAnalytics) {
            usort($departmentAnalytics['events'], $this->sortEvents(...));
        }
        unset($departmentAnalytics);

        uasort($departments, static fn (array $left, array $right): int => strcasecmp($left['title'], $right['title']));

        return [
            'summary' => [
                'total_events' => $totalEvents,
                'completed_events' => $completedEvents,
                'completed_percent' => $this->percent($completedEvents, $totalEvents),
                'reported_events' => $reportedEvents,
                'reported_percent' => $this->percent($reportedEvents, $totalEvents),
                'missing_reports' => $totalEvents - $reportedEvents,
            ],
            'distributions' => $distributions,
            'departments' => $departments,
            'totals' => $totals,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function readReportMetrics(EventReport $report): array
    {
        return [
            'visitorsCount' => $report->getVisitorsCount() ?? 0,
            'participantsCount' => $report->getParticipantsCount() ?? 0,
            'disabledVisitorsCount' => $report->getDisabledVisitorsCount() ?? 0,
            'seniorsVisitorsCount' => $report->getSeniorsVisitorsCount() ?? 0,
            'adultsVisitorsCount' => $report->getAdultsVisitorsCount() ?? 0,
            'youthVisitorsCount' => $report->getYouthVisitorsCount() ?? 0,
            'childrenVisitorsCount' => $report->getChildrenVisitorsCount() ?? 0,
            'mixedAudienceCount' => $report->getMixedAudienceCount() ?? 0,
            'childrenAtRiskCount' => $report->getChildrenAtRiskCount() ?? 0,
            'smoParticipantsCount' => $report->getSmoParticipantsCount() ?? 0,
            'smoFamiliesCount' => $report->getSmoFamiliesCount() ?? 0,
            'youngFamiliesCount' => $report->getYoungFamiliesCount() ?? 0,
            'volunteersCount' => $report->getVolunteersCount() ?? 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyMetricTotals(): array
    {
        return array_fill_keys(array_keys($this->getReportMetricLabels()), 0);
    }

    /**
     * @param array<string, int> $distribution
     */
    private function incrementDistribution(array &$distribution, ?string $label): void
    {
        $label = null === $label || '' === trim($label) ? 'Не указано' : $label;
        $distribution[$label] = ($distribution[$label] ?? 0) + 1;
    }

    private function percent(int $value, int $total): float
    {
        return $total > 0 ? round(($value / $total) * 100, 1) : 0.0;
    }

    private function sortEvents(Event $left, Event $right): int
    {
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
    }
}
