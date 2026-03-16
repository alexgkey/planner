<?php

namespace App\Entity\Enum;

enum TrainingType: string
{
    case QUALIFICATION_UPGRADE = 'qualification_upgrade';
    case PROFESSIONAL_RETRAINING = 'professional_retraining';

    public function getLabel(): string
    {
        return match ($this) {
            self::QUALIFICATION_UPGRADE => 'Повышение квалификации',
            self::PROFESSIONAL_RETRAINING => 'Профпереподготовка',
        };
    }
}