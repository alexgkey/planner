<?php

namespace App\Command;

use App\Service\EventReportReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:event-report-reminders:send',
    description: 'Отправляет напоминания о незаполненных отчетах по мероприятиям.',
)]
final class SendEventReportRemindersCommand extends Command
{
    public function __construct(private readonly EventReportReminderService $eventReportReminderService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sentCount = $this->eventReportReminderService->sendPendingReminders();

        $io->success(sprintf('Отправлено напоминаний: %d.', $sentCount));

        return Command::SUCCESS;
    }
}
