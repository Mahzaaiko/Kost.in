<?php

namespace App\Support;

/** Format angka untuk tampilan (gaya Indonesia). */
final class Format
{
    /** 700000 -> "Rp700.000" */
    public static function rupiah(?int $nilai): string
    {
        return $nilai === null ? '-' : 'Rp'.number_format($nilai, 0, ',', '.');
    }

    /** 850 -> "850 m", 1234 -> "1,2 km" */
    public static function jarak(?float $meter): string
    {
        if ($meter === null) {
            return '-';
        }

        return $meter < 1000
            ? (int) (round($meter / 10) * 10).' m'
            : number_format($meter / 1000, 1, ',', '.').' km';
    }
}
