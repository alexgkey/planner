<?php

namespace App\Entity\Enum;

enum TimesheetStatus: string
{
    case WORK = 'work';
    case DAY_OFF = 'day_off';
    case VACATION = 'vacation';
    case ABSENCE = 'absence';
    case SICK = 'sick';

    public function getCode(): string
    {
        return match ($this) {
            self::WORK => 'Р',
            self::DAY_OFF => 'О',
            self::VACATION => 'Отп',
            self::ABSENCE => 'Н',
            self::SICK => 'Б',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::WORK => 'Рабочий день',
            self::DAY_OFF => 'Отгул',
            self::VACATION => 'Отпуск',
            self::ABSENCE => 'Неявка',
            self::SICK => 'Больничный',
        };
    }

    /**
     * @return array<string, self>
     */
    public static function choices(): array
    {
        return [
            'Р - Рабочий день' => self::WORK,
            'О - Отгул' => self::DAY_OFF,
            'Отп - Отпуск' => self::VACATION,
            'Н - Неявка' => self::ABSENCE,
            'Б - Больничный' => self::SICK,
        ];
    }
}
