<?php

namespace App\Service;

use App\Entity\Enum\EventReportPublicationStatus;
use App\Entity\EventReportPublication;

class EventReportPublicationTextProcessor
{
    public function __construct(private readonly OpenAiPublicationTextReviewer $reviewer)
    {
    }

    public function prepare(EventReportPublication $publication): void
    {
        $sourceText = $publication->getSourceText();
        $prepared = $publication->getPreparedText();

        if (null === $prepared || '' === trim($prepared)) {
            $prepared = $sourceText;
        }

        $normalized = $this->normalizeText($prepared);
        if (null === $normalized) {
            $publication
                ->setPreparedText(null)
                ->setAiProcessedAt(null)
                ->setStatus(EventReportPublicationStatus::DRAFT)
                ->setErrorMessage(null);

            return;
        }

        if ($this->reviewer->isEnabled()) {
            $normalized = $this->normalizeText($this->reviewer->review($normalized));
        }

        $publication
            ->setPreparedText($normalized)
            ->setAiProcessedAt(new \DateTimeImmutable())
            ->setStatus(EventReportPublicationStatus::READY)
            ->setErrorMessage(null);
    }

    private function normalizeText(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        $normalized = preg_replace('/[ \t]+/u', ' ', trim($text));
        $normalized = preg_replace("/\R{3,}/u", "\n\n", (string) $normalized);

        return '' === trim((string) $normalized) ? null : $normalized;
    }
}