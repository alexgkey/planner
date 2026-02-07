<?php

namespace App\Entity\Enum;

enum EventDirection: string
{
    case SPORTS = 'sports';
    case PATRIOTIC = 'patriotic';
    case CULTURAL_LEISURE = 'cultural_leisure';
    case EDUCATIONAL = 'educational';
    case CULTURAL = 'cultural';
    case ACTION = 'action';
    case MASS_CULTURAL = 'mass_cultural';
    case CAREER_GUIDANCE = 'career_guidance';

    public function getLabel(): string
    {
        return match ($this) {
            self::SPORTS => 'Спортивное',
            self::PATRIOTIC => 'Патриотическое',
            self::CULTURAL_LEISURE => 'Культурно-досуговое',
            self::EDUCATIONAL => 'Информационно-просветительское',
            self::CULTURAL => 'Культурное',
            self::ACTION => 'Акция',
            self::MASS_CULTURAL => 'Культурно-массовое',
            self::CAREER_GUIDANCE => 'Профориентационное',
        };
    }
}
