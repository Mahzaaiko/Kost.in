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

    public static function dariLabel(?string $label): ?self
    {
        foreach (self::cases() as $tipe) {
            if ($tipe->label() === $label) {
                return $tipe;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function labels(): array
    {
        return array_map(fn (self $t) => $t->label(), self::cases());
    }
}
