<?php

namespace App\Enums;

enum StatusKos: string
{
    case Draft = 'draft';
    case Aktif = 'aktif';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Aktif => 'Aktif',
        };
    }
}
