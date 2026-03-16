<?php

namespace App\Entity\Enum;

enum EducationType: string
{
    case DIPLOMA = 'diploma';

    public function getLabel(): string
    {
        return match ($this) {
            self::DIPLOMA => 'Диплом',
        };
    }
}