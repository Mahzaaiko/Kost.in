<?php

namespace Tests\Unit;

use App\Services\RekomendasiService;
use PHPUnit\Framework\TestCase;

class RekomendasiServiceTest extends TestCase
{
    private const BOBOT = ['jarak' => 0.5, 'harga' => 0.3, 'fasilitas' => 0.2];

    public function test_contoh_perhitungan_saw_min_max(): void
    {
        // A: paling dekat, paling mahal, fasilitas sedang
        // B: sedang, paling murah, fasilitas paling sedikit
        // C: paling jauh, harga sedang, fasilitas paling banyak
        $skor = (new RekomendasiService)->hitung([
            'A' => ['jarak' => 200, 'harga' => 1_500_000, 'fasilitas' => 6],
            'B' => ['jarak' => 600, 'harga' => 700_000, 'fasilitas' => 4],
            'C' => ['jarak' => 1000, 'harga' => 1_100_000, 'fasilitas' => 8],
        ], self::BOBOT);

        // A = 0,5*1 + 0,3*0 + 0,2*0,5 = 0,6
        // B = 0,5*0,5 + 0,3*1 + 0,2*0 = 0,55
        // C = 0,5*0 + 0,3*0,5 + 0,2*1 = 0,35
        $this->assertSame(['A' => 0.6, 'B' => 0.55, 'C' => 0.35], $skor);
    }

    public function test_satu_kos_atau_nilai_sama_dapat_skor_penuh(): void
    {
        $service = new RekomendasiService;

        $this->assertSame([7 => 1.0], $service->hitung([7 => ['jarak' => 500, 'harga' => 800_000, 'fasilitas' => 3]], self::BOBOT));

        $sama = $service->hitung([
            1 => ['jarak' => 500, 'harga' => 800_000, 'fasilitas' => 3],
            2 => ['jarak' => 500, 'harga' => 800_000, 'fasilitas' => 3],
        ], self::BOBOT);
        $this->assertSame([1 => 1.0, 2 => 1.0], $sama);
    }

    public function test_kosong_menghasilkan_array_kosong(): void
    {
        $this->assertSame([], (new RekomendasiService)->hitung([], self::BOBOT));
    }

    public function test_skor_selalu_antara_0_dan_1(): void
    {
        $data = [];
        mt_srand(42);
        for ($i = 0; $i < 50; $i++) {
            $data[$i] = ['jarak' => mt_rand(50, 9000), 'harga' => mt_rand(5, 30) * 100_000, 'fasilitas' => mt_rand(0, 15)];
        }

        foreach ((new RekomendasiService)->hitung($data, self::BOBOT) as $s) {
            $this->assertGreaterThanOrEqual(0, $s);
            $this->assertLessThanOrEqual(1, $s);
        }
    }
}