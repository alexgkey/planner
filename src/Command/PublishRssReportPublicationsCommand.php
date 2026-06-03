<?php

namespace App\Command;

use App\Service\RssReportPublicationService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:rss-report-publications:publish',
    description: 'Публикует в RSS отчеты за выбранный день, если заполнен текст для госпабликов.',
)]
final class PublishRssReportPublicationsCommand extends Command
{
    public function __construct(
        private readonly RssReportPublicationService $rssReportPublicationService,
        #[Autowire('%env(int:RSS_PUBLICATION_HOUR)%')]
        private readonly int $hour,
        #[Autowire('%env(int:RSS_PUBLICATION_MINUTE)%')]
        private readonly int $minute,
        #[Autowire('%env(string:RSS_PUBLICATION_TIMEZONE)%')]
        private readonly string $timezone,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Показывает, сколько отчетов попало бы в RSS, без записи в базу.')
            ->addOption('date', null, InputOption::VALUE_REQUIRED, 'Дата проверки в формате YYYY-MM-DD. По умолчанию используется текущая дата.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $dateOption = $input->getOption('date');

        try {
            $runAt = null;
            if (is_string($dateOption) && '' !== trim($dateOption)) {
                $runAt = new \DateTimeImmutable(
                    sprintf('%s %02d:%02d:00', trim($dateOption), $this->hour, $this->minute),
                    new \DateTimeZone($this->timezone)
                );
            }
        } catch (\Throwable) {
            $io->error('Неверный формат даты. Используйте YYYY-MM-DD.');

            return Command::INVALID;
        }

        $publishedCount = $this->rssReportPublicationService->publishDailyReports($runAt, $dryRun);

        if ($dryRun) {
            $io->success(sprintf('Dry-run: в RSS было бы опубликовано отчетов: %d.', $publishedCount));
        } else {
            $io->success(sprintf('В RSS опубликовано отчетов: %d.', $publishedCount));
        }

        return Command::SUCCESS;
    }
}
