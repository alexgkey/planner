<?php

namespace App\Entity\Enum;

enum EventAccessibility: string
{
    case PAID = 'paid';
    case FREE = 'free';
    case PUSHKIN_CARD = 'pushkin_card';

    public function getLabel(): string
    {
        return match ($this) {
            self::PAID => 'Платное',
            self::FREE => 'Бесплатное',
            self::PUSHKIN_CARD => 'По Пушкинской карте',
        };
    }
}
