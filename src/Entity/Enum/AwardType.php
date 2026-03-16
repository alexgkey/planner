<?php

namespace App\Entity\Enum;

enum AwardType: string
{
    case GRATITUDE = 'gratitude';
    case HONORARY_CERTIFICATE = 'honorary_certificate';
    case HONORARY_BADGE = 'honorary_badge';
    case MEDAL = 'medal';
    case TITLE = 'title';

    public function getLabel(): string
    {
        return match ($this) {
            self::GRATITUDE => 'Благодарность',
            self::HONORARY_CERTIFICATE => 'Почетная грамота',
            self::HONORARY_BADGE => 'Почетный знак',
            self::MEDAL => 'Медаль',
            self::TITLE => 'Звание',
        };
    }
}