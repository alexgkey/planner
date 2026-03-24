<?php

namespace App\Entity\Enum;

enum EventReportPublicationPlatform: string
{
    case TELEGRAM = 'telegram';
    case VK = 'vk';

    public function getLabel(): string
    {
        return match ($this) {
            self::TELEGRAM => 'Telegram',
            self::VK => 'ВКонтакте',
        };
    }
}