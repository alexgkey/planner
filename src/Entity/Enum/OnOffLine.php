<?php

namespace App\Entity\Enum;

enum OnOffLine: string
{
    case ONLINE = 'online';
    case OFFLINE = 'offline';

    public function getLabel(): string
    {
        return match ($this) {
            self::ONLINE => 'Онлайн',
            self::OFFLINE => 'Оффлайн',
        };
    }
}
