<?php

namespace App;

use App\Message\EventReportReminderCheckMessage;
use App\Message\RssReportPublicationCheckMessage;
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
        #[Autowire('%env(int:RSS_PUBLICATION_HOUR)%')]
        private readonly int $rssPublicationHour,
        #[Autowire('%env(int:RSS_PUBLICATION_MINUTE)%')]
        private readonly int $rssPublicationMinute,
        #[Autowire('%env(string:RSS_PUBLICATION_TIMEZONE)%')]
        private readonly string $rssPublicationTimezone,
    ) {
    }

    public function getSchedule(): SymfonySchedule
    {
        $reminderCronExpression = sprintf('%d %d * * *', $this->eventReportReminderMinute, $this->eventReportReminderHour);
        $rssCronExpression = sprintf('%d %d * * *', $this->rssPublicationMinute, $this->rssPublicationHour);

        return (new SymfonySchedule())
            ->stateful($this->cache) // ensure missed tasks are executed
            ->processOnlyLastMissedRun(true) // ensure only last missed task is run
            ->with(RecurringMessage::cron(
                $reminderCronExpression,
                new EventReportReminderCheckMessage(),
                new \DateTimeZone($this->eventReportReminderTimezone)
            ))
            ->with(RecurringMessage::cron(
                $rssCronExpression,
                new RssReportPublicationCheckMessage(),
                new \DateTimeZone($this->rssPublicationTimezone)
            ))
        ;
    }
}
