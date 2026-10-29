<?php

namespace App\Support;

/**
 * Dekoder Open Location Code (Plus Code), algoritma terbuka dari Google.
 * Dipakai untuk alamat di spreadsheet yang ditulis seperti "CRC6+8VF, ...".
 *
 * Kode pendek (4 karakter sebelum "+") dipulihkan memakai titik acuan terdekat,
 * yaitu titik kampus, sesuai algoritma recoverNearest pada spesifikasi OLC.
 */
final class PlusCode
{
    private const ALFABET = '23456789CFGHJMPQRVWX';

    private const RESOLUSI_PASANGAN = [20.0, 1.0, 0.05, 0.0025, 0.000125];

    private const BARIS_GRID = 5;

    private const KOLOM_GRID = 4;

    /** Cari Plus Code di dalam teks. Mengembalikan kodenya (huruf besar) atau null. */
    public static function cariDiTeks(string $teks): ?string
    {
        $pola = '/(?<![A-Z0-9])(['.self::ALFABET.']{2,8}\+['.self::ALFABET.']{2,3})(?![A-Z0-9])/i';

        return preg_match($pola, $teks, $m) ? strtoupper($m[1]) : null;
    }

    /** Ubah kode (lengkap atau pendek) ke titik tengah selnya. */
    public static function keKoordinat(string $kode, Koordinat $acuan): ?Koordinat
    {
        $kode = strtoupper(trim($kode));
        if (! self::valid($kode)) {
            return null;
        }

        $posPlus = strpos($kode, '+');
        if ($posPlus < 8) {
            return self::pulihkanTerdekat($kode, $acuan);
        }

        [$lat, $lng] = self::decode($kode);

        return new Koordinat(lat: $lat, lng: $lng);
    }

    /**
     * Perkiraan ukuran sel kode dalam meter (sisi terpanjang).
     * Contoh: 2 karakter setelah "+" sekitar 14 m, 3 karakter sekitar 3,5 m.
     */
    public static function presisiMeter(string $kode): float
    {
        $belakang = strlen(explode('+', strtoupper(trim($kode)).'+')[1]);
        $resLat = $resLng = self::RESOLUSI_PASANGAN[4];
        for ($i = 2; $i < $belakang; $i++) {
            $resLat /= self::BARIS_GRID;
            $resLng /= self::KOLOM_GRID;
        }

        return max($resLat, $resLng) * 110_574;
    }

    private static function valid(string $kode): bool
    {
        if (substr_count($kode, '+') !== 1) {
            return false;
        }
        [$depan, $belakang] = explode('+', $kode);

        return strlen($depan) % 2 === 0 && strlen($depan) >= 2 && strlen($depan) <= 8
            && strlen($belakang) >= 2
            && strspn($depan.$belakang, self::ALFABET) === strlen($depan.$belakang);
    }

    /** @return array{0: float, 1: float} titik tengah [lat, lng] dari kode lengkap */
    private static function decode(string $kodeLengkap): array
    {
        $kode = str_replace('+', '', $kodeLengkap);
        $lat = -90.0;
        $lng = -180.0;
        $resLat = $resLng = 20.0;

        $jumlahPasangan = intdiv(min(strlen($kode), 10), 2);
        for ($i = 0; $i < $jumlahPasangan; $i++) {
            $resLat = $resLng = self::RESOLUSI_PASANGAN[$i];
            $lat += strpos(self::ALFABET, $kode[2 * $i]) * $resLat;
            $lng += strpos(self::ALFABET, $kode[2 * $i + 1]) * $resLng;
        }

        // Karakter ke-11 dan seterusnya: grid 5 baris x 4 kolom.
        for ($i = 10; $i < strlen($kode); $i++) {
            $nilai = strpos(self::ALFABET, $kode[$i]);
            $resLat /= self::BARIS_GRID;
            $resLng /= self::KOLOM_GRID;
            $lat += intdiv($nilai, self::KOLOM_GRID) * $resLat;
            $lng += ($nilai % self::KOLOM_GRID) * $resLng;
        }

        return [$lat + $resLat / 2, $lng + $resLng / 2];
    }

    /** Encode titik acuan, hanya sebanyak $panjang karakter pertama (tanpa "+"). */
    private static function awalanDari(Koordinat $acuan, int $panjang): string
    {
        $lat = $acuan->lat + 90;
        $lng = $acuan->lng + 180;
        $hasil = '';

        for ($i = 0; strlen($hasil) < $panjang; $i++) {
            $res = self::RESOLUSI_PASANGAN[$i];
            $dLat = (int) floor($lat / $res);
            $dLng = (int) floor($lng / $res);
            $lat -= $dLat * $res;
            $lng -= $dLng * $res;
            $hasil .= self::ALFABET[$dLat].self::ALFABET[$dLng];
        }

        return substr($hasil, 0, $panjang);
    }

    private static function pulihkanTerdekat(string $kodePendek, Koordinat $acuan): Koordinat
    {
        $kurang = 8 - strpos($kodePendek, '+');
        $resolusi = 20 ** (2 - $kurang / 2);
        $setengah = $resolusi / 2;

        $lengkap = self::awalanDari($acuan, $kurang).$kodePendek;
        [$lat, $lng] = self::decode($lengkap);

        // Pilih sel terdekat dengan titik acuan.
        if ($acuan->lat + $setengah < $lat && $lat - $resolusi >= -90) {
            $lat -= $resolusi;
        } elseif ($acuan->lat - $setengah > $lat && $lat + $resolusi <= 90) {
            $lat += $resolusi;
        }
        if ($acuan->lng + $setengah < $lng) {
            $lng -= $resolusi;
        } elseif ($acuan->lng - $setengah > $lng) {
            $lng += $resolusi;
        }

        return new Koordinat(lat: $lat, lng: $lng);
    }
}