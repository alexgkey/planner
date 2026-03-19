<?php

namespace App\Entity\Enum;

enum EventReportPublicationStatus: string
{
    case DRAFT = 'draft';
    case READY = 'ready';
    case PUBLISHED = 'published';
    case SKIPPED = 'skipped';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => 'Черновик',
            self::READY => 'Готово к публикации',
            self::PUBLISHED => 'Опубликовано',
            self::SKIPPED => 'Не публиковать',
            self::FAILED => 'Ошибка публикации',
        };
    }
}