<?php

namespace App\Service;

use App\Entity\Enum\EventReportPublicationPlatform;
use App\Entity\EventReportPublication;
use App\Entity\User;

class EventReportPublicationPublisher
{
    public function __construct(private readonly TelegramPublicationClient $telegramPublicationClient)
    {
    }

    public function publish(EventReportPublication $publication, ?User $actor = null): void
    {
        if (!$publication->hasPreparedText()) {
            throw new \RuntimeException('У публикации нет подготовленного текста.');
        }

        $result = match ($publication->getPlatform()) {
            EventReportPublicationPlatform::TELEGRAM => $this->telegramPublicationClient->publish($publication),
        };

        $publication
            ->markAsPublished($result['message_id'] ?? null)
            ->setLastEditedBy($actor);
    }
}