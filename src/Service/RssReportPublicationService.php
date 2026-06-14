<?php

namespace App\Service;

use App\Entity\EventReportPublication;
use App\Repository\EventReportPublicationRepository;
use App\Repository\EventReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class RssReportPublicationService
{
    private readonly \DateTimeZone $timezoneObject;

    public function __construct(
        private readonly EventReportRepository $eventReportRepository,
        private readonly EventReportPublicationRepository $publicationRepository,
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%env(string:RSS_PUBLICATION_TIMEZONE)%')]
        private readonly string $timezone,
    ) {
        $this->timezoneObject = new \DateTimeZone($this->timezone);
    }

    public function publishDailyReports(?\DateTimeImmutable $runAt = null, bool $dryRun = false): int
    {
        $runAt = $runAt?->setTimezone($this->timezoneObject) ?? new \DateTimeImmutable('now', $this->timezoneObject);
        [$start, $end] = $this->getDayBounds($runAt);

        $reports = $this->eventReportRepository->findReportsReadyForRssPublication($start, $end);
        $publishedCount = 0;

        foreach ($reports as $report) {
            if (null !== $this->publicationRepository->findPublishedForReportAndPlatform($report, EventReportPublication::PLATFORM_RSS)) {
                continue;
            }

            if ($dryRun) {
                ++$publishedCount;
                continue;
            }

            $publication = (new EventReportPublication())
                ->setEventReport($report)
                ->setPlatform(EventReportPublication::PLATFORM_RSS)
                ->setStatus(EventReportPublication::STATUS_PUBLISHED)
                ->setSourceText($report->getPublicReportText())
                ->setPreparedText($report->getPublicReportText())
                ->setCreatedAtValue($runAt)
                ->setPublishedAt($runAt)
            ;

            $this->entityManager->persist($publication);
            ++$publishedCount;
        }

        if (!$dryRun && $publishedCount > 0) {
            $this->entityManager->flush();
        }

        return $publishedCount;
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: \DateTimeImmutable}
     */
    private function getDayBounds(\DateTimeImmutable $runAt): array
    {
        $start = $runAt->setTime(0, 0, 0);
        $end = $start->modify('+1 day');

        return [$start, $end];
    }
}
