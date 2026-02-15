<?php

namespace App\Enums;

enum ParsingStatus: int
{
    case QUEUED = 0;
    case PARSING = 1;
    case PARSED = 2;
    case IMPORTING = 3;
    case DONE = 4;
    case NEEDS_MANUAL = 5;

    public function label(): string
    {
        return match ($this) {
            self::QUEUED => 'В очереди',
            self::PARSING => 'Парсинг',
            self::PARSED => 'Распарсено',
            self::IMPORTING => 'Импорт',
            self::DONE => 'Готово',
            self::NEEDS_MANUAL => 'Капча',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::QUEUED => 'secondary',
            self::NEEDS_MANUAL => 'destructive',
            default => 'default',
        };
    }

    public static function labels(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $case) => $case->label(), self::cases())
        );
    }
}
