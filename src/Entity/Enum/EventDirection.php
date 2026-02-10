<?php

namespace App\Entity\Enum;

enum EventDirection: string
{
    case CULTURAL_MASS_EVENTS = 'cultural_mass_events';
    case FOLK_CULTURE_REVIVAL = 'folk_culture_revival';
    case PATRIOTIC_EDUCATION = 'patriotic_education';
    case MORAL_AESTHETIC_EDUCATION = 'moral_aesthetic_education';
    case ECOLOGICAL_EDUCATION = 'ecological_education';
    case SPORTS_HEALTH_EVENTS = 'sports_health_events';
    case CAREER_GUIDANCE = 'career_guidance';
    case WORK_WITH_DISABLED = 'work_with_disabled';
    case PREVENTION_OF_NEGATIVE_PHENOMENA = 'prevention_of_negative_phenomena';
    case SVO_THEMED_EVENTS = 'smo_themed_events';

    public function getLabel(): string
    {
        return match ($this) {
            self::CULTURAL_MASS_EVENTS => 'Культурно-массовые мероприятия',
            self::FOLK_CULTURE_REVIVAL => 'Возрождение и сохранение традиционной народной культуры',
            self::PATRIOTIC_EDUCATION => 'Гражданско-патриотическое воспитание',
            self::MORAL_AESTHETIC_EDUCATION => 'Нравственно-эстетическое воспитание',
            self::ECOLOGICAL_EDUCATION => 'Экологическое воспитание',
            self::SPORTS_HEALTH_EVENTS => 'Спортивные и физкультурно-оздоровительные мероприятия',
            self::CAREER_GUIDANCE => 'Профориентационная работа',
            self::WORK_WITH_DISABLED => 'Работа с инвалидами и лицами с ОВЗ',
            self::PREVENTION_OF_NEGATIVE_PHENOMENA => 'Профилактика негативных явлений',
            self::SVO_THEMED_EVENTS => 'Мероприятия на тему СВО',
        };
    }
}
