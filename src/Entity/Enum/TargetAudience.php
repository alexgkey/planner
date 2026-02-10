<?php

namespace App\Entity\Enum;

enum TargetAudience: string
{
    case mixed = 'mixed';
    case UP_TO_14 = 'up_to_14';
    case FROM_14_TO_35 = 'from_14_to_35';
    case FROM_36_TO_55 = 'from_36_to_55';
    case ABOVE_55 = 'above_55';

    public function getLabel(): string
    {
        return match ($this) {
            self::mixed => 'Смешанная',
            self::UP_TO_14 => 'до 14',
            self::FROM_14_TO_35 => '14-35',
            self::FROM_36_TO_55 => '36-55',
            self::ABOVE_55 => '55+',
        };
    }
}
