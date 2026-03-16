<?php

namespace App\MessageHandler;

use App\Message\EventReportReminderCheckMessage;
use App\Service\EventReportReminderService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class EventReportReminderCheckMessageHandler
{
    public function __construct(private readonly EventReportReminderService $eventReportReminderService)
    {
    }

    public function __invoke(EventReportReminderCheckMessage $message): void
    {
        $this->eventReportReminderService->sendPendingReminders();
    }
}
