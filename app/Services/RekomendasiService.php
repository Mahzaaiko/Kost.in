<?php

namespace App\Services;

/**
 * Skor rekomendasi metode SAW (Simple Additive Weighting) dengan normalisasi min-max.
 *
 * Kriteria dan bobot (config/kostin.php):
 * - jarak ke kampus (cost: makin dekat makin baik)
 * - harga per bulan (cost: makin murah makin baik)
 * - jumlah fasilitas (benefit: makin banyak makin baik)
 *
 * Normalisasi min-max dipakai, bukan rumus SAW klasik min/x, supaya satu kos
 * yang sangat dekat tidak membuat nilai jarak kos lain jatuh terlalu jauh.
 * Skor dihitung relatif terhadap kos yang sedang lolos filter.
 */
class RekomendasiService
{
    /**
     * @param  array<int|string, array{jarak: float, harga: int, fasilitas: int}>  $alternatif  kunci = id kos
     * @param  array{jarak: float, harga: float, fasilitas: float}|null  $bobot
     * @return array<int|string, float> skor 0..1 per id kos, urutan sama dengan input
     */
    public function hitung(array $alternatif, ?array $bobot = null): array
    {
        if ($alternatif === []) {
            return [];
        }

        $bobot ??= config('kostin.rekomendasi.bobot');

        $rentang = [];
        foreach (['jarak', 'harga', 'fasilitas'] as $kriteria) {
            $nilai = array_column($alternatif, $kriteria);
            $rentang[$kriteria] = [min($nilai), max($nilai)];
        }

        $skor = [];
        foreach ($alternatif as $id => $a) {
            $skor[$id] = round(
                $bobot['jarak'] * $this->cost($a['jarak'], ...$rentang['jarak'])
                + $bobot['harga'] * $this->cost($a['harga'], ...$rentang['harga'])
                + $bobot['fasilitas'] * $this->benefit($a['fasilitas'], ...$rentang['fasilitas']),
                4,
            );
        }

        return $skor;
    }

    /** Makin kecil makin baik. Semua nilai sama = semua dapat 1. */
    private function cost(float $x, float $min, float $max): float
    {
        return $max == $min ? 1.0 : ($max - $x) / ($max - $min);
    }

    /** Makin besar makin baik. Semua nilai sama = semua dapat 1. */
    private function benefit(float $x, float $min, float $max): float
    {
        return $max == $min ? 1.0 : ($x - $min) / ($max - $min);
    }
}