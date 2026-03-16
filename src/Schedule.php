<?php

namespace App;

use App\Message\EventReportReminderCheckMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule as SymfonySchedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule]
class Schedule implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
        #[Autowire('%env(int:EVENT_REPORT_REMINDER_HOUR)%')]
        private readonly int $eventReportReminderHour,
        #[Autowire('%env(int:EVENT_REPORT_REMINDER_MINUTE)%')]
        private readonly int $eventReportReminderMinute,
        #[Autowire('%env(string:EVENT_REPORT_REMINDER_TIMEZONE)%')]
        private readonly string $eventReportReminderTimezone,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        $cronExpression = sprintf('%d %d * * *', $this->eventReportReminderMinute, $this->eventReportReminderHour);

        return (new SymfonySchedule())
            ->stateful($this->cache) // ensure missed tasks are executed
            ->processOnlyLastMissedRun(true) // ensure only last missed task is run
            ->with(RecurringMessage::cron(
                $cronExpression,
                new EventReportReminderCheckMessage(),
                new \DateTimeZone($this->eventReportReminderTimezone)
            ))
        ;
    }
}
