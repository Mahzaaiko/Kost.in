<?php

namespace Tests\Unit;

use App\Support\Koordinat;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class KoordinatTest extends TestCase
{
    public function test_ewkt_memakai_urutan_lng_lat(): void
    {
        $k = new Koordinat(lat: -6.5895875, lng: 106.8067344);

        $this->assertSame('SRID=4326;POINT(106.8067344 -6.5895875)', $k->toEwkt());
    }

    public function test_membaca_ewkb_hex_point_dengan_srid(): void
    {
        // Hasil PostGIS: SELECT ST_GeogFromText('SRID=4326;POINT(106.8067344 -6.5895875)')
        $hex = bin2hex(pack('C', 1).pack('V', 0x20000001).pack('V', 4326).pack('e', 106.8067344).pack('e', -6.5895875));

        $k = Koordinat::fromEwkbHex($hex);

        $this->assertEqualsWithDelta(-6.5895875, $k->lat, 1e-9);
        $this->assertEqualsWithDelta(106.8067344, $k->lng, 1e-9);
    }

    public function test_latitude_di_luar_rentang_ditolak(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Koordinat(lat: 106.8, lng: -6.5); // lat/lng tertukar
    }

    public function test_jarak_haversine_masuk_akal(): void
    {
        // 0,001 derajat lintang kira-kira 111 m.
        $a = new Koordinat(lat: -6.5895875, lng: 106.8067344);
        $b = new Koordinat(lat: -6.5885875, lng: 106.8067344);

        $this->assertEqualsWithDelta(111.2, $a->jarakMeterKe($b), 0.5);
    }
}
