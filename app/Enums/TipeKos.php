<?php

namespace App\Enums;

enum TipeKos: string
{
    case Putri = 'putri';
    case Putra = 'putra';
    case Campur = 'campur';

    /** Label yang ditampilkan di UI, mengikuti frontend ("Putri", "Putra", "Campur"). */
    public function label(): string
    {
        return match ($this) {
            self::Putri => 'Putri',
            self::Putra => 'Putra',
            self::Campur => 'Campur',
        };
    }
}
