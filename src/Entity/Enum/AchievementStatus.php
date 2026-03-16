<?php

namespace App\Entity\Enum;

enum AchievementStatus: string
{
    case MUNICIPAL = 'municipal';
    case REGIONAL = 'regional';
    case INTERREGIONAL = 'interregional';
    case FEDERAL = 'federal';
    case INTERNATIONAL = 'international';

    public function getLabel(): string
    {
        return match ($this) {
            self::MUNICIPAL => 'Муниципальный',
            self::REGIONAL => 'Областной',
            self::INTERREGIONAL => 'Межрегиональный',
            self::FEDERAL => 'Федеральный',
            self::INTERNATIONAL => 'Международный',
        };
    }
}