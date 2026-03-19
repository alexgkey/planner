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

    public function backfillTelegramPublications(?User $actor = null): int
    {
        $created = 0;

        foreach ($this->eventReportRepository->findWithoutPublicationForPlatform(EventReportPublicationPlatform::TELEGRAM) as $report) {
            $this->syncTelegramPublication($report, $actor);
            ++$created;
        }

        if ($created > 0) {
            $this->entityManager->flush();
        }

        return $created;
    }

    public function syncTelegramPublication(EventReport $report, ?User $actor = null): EventReportPublication
    {
        /** @var EventReportPublication|null $publication */
        $publication = $this->publicationRepository->findOneBy([
            'eventReport' => $report,
            'platform' => EventReportPublicationPlatform::TELEGRAM,
        ]);

        $sourceText = $this->normalizeText($report->getPublicReportText());

        if (null === $publication) {
            $publication = (new EventReportPublication())
                ->setEventReport($report)
                ->setPlatform(EventReportPublicationPlatform::TELEGRAM)
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