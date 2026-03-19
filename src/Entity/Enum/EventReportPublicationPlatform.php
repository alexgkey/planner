<?php

namespace App\Entity\Enum;

enum EventReportPublicationPlatform: string
{
    case TELEGRAM = 'telegram';

    public function getLabel(): string
    {
        return match ($this) {
            self::TELEGRAM => 'Telegram',
        };
    }
}