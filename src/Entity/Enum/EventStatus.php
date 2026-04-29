<?php

namespace App\Entity\Enum;

enum EventStatus: string
{
    case PLANNED = 'planned';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::PLANNED => 'Запланировано',
            self::COMPLETED => 'Проведено',
            self::CANCELLED => 'Отменено',
        };
    }
}
