<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Titik WGS84 (EPSG:4326).
 *
 * Perhatian urutan: manusia menulis "lat, lng", sedangkan PostGIS/WKT
 * memakai "X Y" = "lng lat". Semua konversi ke database lewat kelas ini
 * supaya urutannya tidak tertukar.
 */
final class Koordinat
{
    public function __construct(
        public readonly float $lat,
        public readonly float $lng,
    ) {
        if ($lat < -90 || $lat > 90) {
            throw new InvalidArgumentException("Latitude {$lat} di luar rentang -90 sampai 90.");
        }
        if ($lng < -180 || $lng > 180) {
            throw new InvalidArgumentException("Longitude {$lng} di luar rentang -180 sampai 180.");
        }
    }

    /** EWKT untuk PostGIS, contoh: SRID=4326;POINT(106.8067344 -6.5895875) */
    public function toEwkt(): string
    {
        return sprintf('SRID=4326;POINT(%.7F %.7F)', $this->lng, $this->lat);
    }

    /**
     * Baca nilai kolom geography(Point) yang dikirim PostgreSQL dalam bentuk
     * EWKB hex, misalnya "0101000020E6100000...".
     */
    public static function fromEwkbHex(string $hex): self
    {
        $bin = @hex2bin($hex);
        if ($bin === false || strlen($bin) < 21) {
            throw new InvalidArgumentException('Nilai geometri bukan EWKB hex yang valid.');
        }

        $littleEndian = ord($bin[0]) === 1;
        $u32 = $littleEndian ? 'V' : 'N';
        $type = unpack($u32, substr($bin, 1, 4))[1];

        $hasSrid = ($type & 0x20000000) !== 0;
        if (($type & 0xFF) !== 1) {
            throw new InvalidArgumentException('Geometri bukan POINT.');
        }

        $offset = $hasSrid ? 9 : 5;
        $double = $littleEndian ? 'e' : 'E';
        $x = unpack($double, substr($bin, $offset, 8))[1];
        $y = unpack($double, substr($bin, $offset + 8, 8))[1];

        return new self(lat: $y, lng: $x);
    }

    /** Jarak garis lurus (haversine) dalam meter. Dipakai untuk validasi di PHP. */
    public function jarakMeterKe(self $lain): float
    {
        $r = 6371008.8;
        $dLat = deg2rad($lain->lat - $this->lat);
        $dLng = deg2rad($lain->lng - $this->lng);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($this->lat)) * cos(deg2rad($lain->lat)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /** Titik kampus dari config/kostin.php. */
    public static function kampus(): self
    {
        return new self(
            lat: (float) config('kostin.kampus.lat'),
            lng: (float) config('kostin.kampus.lng'),
        );
    }

    /** @return array{lat: float, lng: float} */
    public function toArray(): array
    {
        return ['lat' => $this->lat, 'lng' => $this->lng];
    }
}
