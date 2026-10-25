<?php

namespace App\Enums;

/** Pilihan periode minimal sewa. Nilai = jumlah bulan, label = teks di form admin. */
enum PeriodeSewa: int
{
    case SatuBulan = 1;
    case TigaBulan = 3;
    case EnamBulan = 6;
    case Tahunan = 12;

    public function label(): string
    {
        return match ($this) {
            self::SatuBulan => '1 Bulan',
            self::TigaBulan => '3 Bulan',
            self::EnamBulan => '6 Bulan',
            self::Tahunan => 'Tahunan',
        };
    }

    public static function dariLabel(?string $label): ?self
    {
        foreach (self::cases() as $periode) {
            if ($periode->label() === $label) {
                return $periode;
            }
        }

        return null;
    }

    /** @return list<string> */
    public static function labels(): array
    {
        return array_map(fn (self $p) => $p->label(), self::cases());
    }
}
