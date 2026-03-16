<?php

namespace App\Command;

use App\Service\EventReportReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Проверить, сколько напоминаний было бы отправлено, без реальной отправки писем и изменений в базе.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $sentCount = $this->eventReportReminderService->sendPendingReminders(null, $dryRun);

        if ($dryRun) {
            $io->success(sprintf('Dry-run: было бы отправлено напоминаний: %d.', $sentCount));
        } else {
            $io->success(sprintf('Отправлено напоминаний: %d.', $sentCount));
        }

        return Command::SUCCESS;
    }
}