<?php

namespace App\Entity\Enum;

enum EventLevel: string
{
    case MUNICIPAL = 'municipal';
    case URBAN = 'urban';
    case DISTRICT = 'district';
    case REGIONAL = 'regional';
    case PROVINCE = 'province';
    case NATIONAL = 'national';
    case INTERNATIONAL = 'international';

    public function getLabel(): string
    {
        return match ($this) {
            self::MUNICIPAL => 'Муниципальный',
            self::URBAN => 'Городской',
            self::DISTRICT => 'Окружной',
            self::REGIONAL => 'Региональный',
            self::PROVINCE => 'Областной',
            self::NATIONAL => 'Всероссийский',
            self::INTERNATIONAL => 'Международный',
        };
    }
}
