<?php

namespace Tests\Unit;

use App\Support\Koordinat;
use App\Support\PlusCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PlusCodeTest extends TestCase
{
    private Koordinat $kampus;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kampus = new Koordinat(lat: -6.5895875, lng: 106.8067344);
    }

    /** Nilai acuan dari library resmi openlocationcode (Python). */
    public static function kodeAcuan(): array
    {
        return [
            'kampus SV IPB' => ['CR64+5M9', -6.5895875, 106.8067344],
            'kode pendek 1' => ['CRC6+8VF', -6.579187, 106.812172],
            'kode pendek 2' => ['CR45+FGH', -6.593813, 106.808859],
            'kode lengkap' => ['6P58CR87+C9Q', -6.583913, 106.813484],
        ];
    }

    #[DataProvider('kodeAcuan')]
    public function test_dekode_sesuai_library_resmi(string $kode, float $lat, float $lng): void
    {
        $titik = PlusCode::keKoordinat($kode, $this->kampus);

        $this->assertEqualsWithDelta($lat, $titik->lat, 2e-6);
        $this->assertEqualsWithDelta($lng, $titik->lng, 2e-6);
    }

    public function test_perkiraan_ketelitian_kode(): void
    {
        $this->assertEqualsWithDelta(13.8, PlusCode::presisiMeter('CR66+V3'), 0.1);
        $this->assertEqualsWithDelta(3.5, PlusCode::presisiMeter('CR64+5M9'), 0.1);
    }

    public function test_menemukan_plus_code_di_dalam_alamat(): void
    {
        $this->assertSame('CRG4+CJX', PlusCode::cariDiTeks('CRG4+CJX, Gg. Kananga, RT.03/RW.12, Bantarjati'));
        $this->assertNull(PlusCode::cariDiTeks('Jl. Lodaya II No.9, RT.02/RW.02, Cilibende'));
    }

    public function test_kode_tidak_valid_menghasilkan_null(): void
    {
        $this->assertNull(PlusCode::keKoordinat('ABCD+12', $this->kampus));
        $this->assertNull(PlusCode::keKoordinat('CR64', $this->kampus));
    }
}