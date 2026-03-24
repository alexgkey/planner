<?php

namespace App\Service;

use App\Entity\Enum\EventReportPublicationPlatform;
use App\Entity\Enum\EventReportPublicationStatus;
use App\Entity\EventReport;
use App\Entity\EventReportPublication;
use App\Entity\User;
use App\Repository\EventReportPublicationRepository;
use App\Repository\EventReportRepository;
use Doctrine\ORM\EntityManagerInterface;

class EventReportPublicationManager
{
    public function __construct(
        private readonly EventReportPublicationRepository $publicationRepository,
        private readonly EventReportRepository $eventReportRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function backfillPlatformPublications(?User $actor = null): int
    {
        $created = 0;

        foreach (EventReportPublicationPlatform::cases() as $platform) {
            foreach ($this->eventReportRepository->findWithoutPublicationForPlatform($platform) as $report) {
                $this->syncPublication($report, $platform, $actor);
                ++$created;
            }
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        return $created;
    }

    /**
     * @return EventReportPublication[]
     */
    public function syncAllPublications(EventReport $report, ?User $actor = null): array
    {
        $publications = [];

        foreach (EventReportPublicationPlatform::cases() as $platform) {
            $publications[] = $this->syncPublication($report, $platform, $actor);
        }

        return $publications;
    }

    public function syncPublication(EventReport $report, EventReportPublicationPlatform $platform, ?User $actor = null): EventReportPublication
    {
        /** @var EventReportPublication|null $publication */
        $publication = $this->publicationRepository->findOneBy([
            'eventReport' => $report,
            'platform' => $platform,
        ]);

        $sourceText = $this->normalizeText($report->getPublicReportText());

        if (null === $publication) {
            $publication = (new EventReportPublication())
                ->setEventReport($report)
                ->setPlatform($platform)
                ->setCreatedBy($actor ?? $report->getCreator())
                ->setSourceText($sourceText)
                ->setPreparedText($sourceText)
                ->setStatus(null !== $sourceText ? EventReportPublicationStatus::READY : EventReportPublicationStatus::DRAFT);

            $report->addPublication($publication);
            $this->entityManager->persist($publication);

            return $publication;
        }

        $previousSource = $publication->getSourceText();
        $publication->setSourceText($sourceText);

        if ($publication->getPreparedText() === $previousSource || null === $publication->getPreparedText()) {
            $publication->setPreparedText($sourceText);
        }

        if (!in_array($publication->getStatus(), [EventReportPublicationStatus::PUBLISHED, EventReportPublicationStatus::SKIPPED], true)) {
            $publication->setStatus(null !== $publication->getPreparedText() ? EventReportPublicationStatus::READY : EventReportPublicationStatus::DRAFT);
        }

        $publication->setLastEditedBy($actor);

        return $publication;
    }

    public function markSkipped(EventReportPublication $publication, ?User $actor = null): EventReportPublication
    {
        $publication->markSkipped()->setLastEditedBy($actor);

        return $publication;
    }

    private function normalizeText(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        $normalized = trim($text);

        return '' === $normalized ? null : $normalized;
    }
}