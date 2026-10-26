<?php

namespace Tests\Feature\Routing;

use App\Enums\StatusKos;
use App\Enums\TipeKos;
use App\Models\Kos;
use App\Services\Routing\ImportJaringanJalan;
use App\Services\Routing\RuteService;
use App\Support\Koordinat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Jaringan uji (satuan meter, x ke timur dan y ke utara dari titik kampus):
 *
 *   N5 (-200,400) ─────────── N6 (0,400) ─────────── N4 (200,400)
 *        │                                                  │
 *        │      [footway terpisah (-110..-90, 200)]          │   K1 (210,300)
 *        │  K2 (-100,205)                                    │
 *        │                                                  │
 *   N1 (-200,-20) ───── K3 (-100,-30) ──── N2 (50,-20) ──── N3 (200,-20)
 *                        kampus (0,0) 20 m di utara jalan bawah
 */
class RuteTest extends TestCase
{
    use RefreshDatabase;

    private const M_PER_DERAJAT_LAT = 110_580;

    private const M_PER_DERAJAT_LNG = 110_584; // 111.320 x cos(6,59°)

    private int $idSimpul = 0;

    private function titik(float $x, float $y): Koordinat
    {
        $k = Koordinat::kampus();

        return new Koordinat(lat: $k->lat + $y / self::M_PER_DERAJAT_LAT, lng: $k->lng + $x / self::M_PER_DERAJAT_LNG);
    }

    private function ruas(int $source, array $dari, int $target, array $ke, string $nama = 'Jalan Uji'): void
    {
        $a = $this->titik(...$dari);
        $b = $this->titik(...$ke);
        DB::insert(
            "INSERT INTO ruas_jalan (sumber, nama, jenis, source, target, panjang_m, geom)
             SELECT 'osm', ?, 'residential', ?, ?, ST_Length(g::geography), g
             FROM (SELECT ST_SetSRID(ST_MakeLine(ST_MakePoint(?, ?), ST_MakePoint(?, ?)), 4326) AS g) t",
            [$nama, $source, $target, $a->lng, $a->lat, $b->lng, $b->lat],
        );
    }

    private function jaringanUji(): void
    {
        $this->ruas(1, [-200, -20], 2, [50, -20], 'Jalan Bawah Barat');
        $this->ruas(2, [50, -20], 3, [200, -20], 'Jalan Bawah Timur');
        $this->ruas(3, [200, -20], 4, [200, 400], 'Jalan Timur');
        $this->ruas(1, [-200, -20], 5, [-200, 400], 'Jalan Barat');
        $this->ruas(5, [-200, 400], 6, [0, 400], 'Jalan Atas');
        $this->ruas(6, [0, 400], 4, [200, 400], 'Jalan Atas');
        $this->ruas(7, [-110, 200], 8, [-90, 200], 'Footway Terpisah');

        app(ImportJaringanJalan::class)->tandaiKomponenUtama();
    }

    private function kos(string $nama, float $x, float $y, StatusKos $status = StatusKos::Aktif): Kos
    {
        return Kos::create([
            'nama' => $nama, 'alamat' => 'Jl. Uji', 'tipe' => TipeKos::Putri, 'harga_per_bulan' => 800_000,
            'periode_minimal_bulan' => 1, 'status' => $status, 'lokasi' => $this->titik($x, $y),
        ]);
    }

    public function test_komponen_terpisah_tidak_ditandai_utama(): void
    {
        $this->jaringanUji();

        $this->assertSame(6, DB::table('ruas_jalan')->where('utama', true)->count());
        $this->assertFalse((bool) DB::table('ruas_jalan')->where('nama', 'Footway Terpisah')->value('utama'));
    }

    public function test_rute_terpendek_lewat_jalan_dan_bukan_garis_lurus(): void
    {
        $this->jaringanUji();
        $kos = $this->kos('Kos Timur', 210, 300);

        $rute = app(RuteService::class)->untukKos($kos);

        // 10 m ke jalan + 320 m ke N3 + 150 m ke N2 + 50 m ke titik tempel kampus + 20 m ke kampus = 550 m
        $this->assertTrue($rute['tersedia']);
        $this->assertEqualsWithDelta(550, $rute['panjang_m'], 3);
        $this->assertSame(7, $rute['durasi_menit'], '550 m / 80 m per menit = 6,9 lalu dibulatkan ke atas');
        $this->assertEqualsWithDelta(10, $rute['jarak_ke_jalan_m'], 1);
        $this->assertNull($rute['catatan']);

        $garisLurus = $kos->lokasi->jarakMeterKe(Koordinat::kampus());
        $this->assertGreaterThan($garisLurus + 100, $rute['panjang_m']);

        // Garis rute mulai di titik kos, berakhir di titik kampus, dan melewati simpul N3 (pojok kanan bawah).
        $koordinat = $rute['geometri']['coordinates'];
        $this->assertSame('LineString', $rute['geometri']['type']);
        $this->assertEqualsWithDelta([$kos->lokasi->lng, $kos->lokasi->lat], $koordinat[0], 1e-6);
        $this->assertEqualsWithDelta([Koordinat::kampus()->lng, Koordinat::kampus()->lat], end($koordinat), 1e-6);
        $n3 = $this->titik(200, -20);
        $this->assertTrue(collect($koordinat)->contains(fn ($c) => abs($c[0] - $n3->lng) < 1e-6 && abs($c[1] - $n3->lat) < 1e-6));
    }

    public function test_titik_menempel_ke_jaringan_utama_walau_ada_jalan_terpisah_yang_lebih_dekat(): void
    {
        $this->jaringanUji();
        $kos = $this->kos('Kos Barat', -100, 205);

        $rute = app(RuteService::class)->untukKos($kos);

        // Footway terpisah hanya 5 m, tapi kos ditempel ke Jalan Barat (100 m) supaya tetap terhubung.
        // 100 + 225 ke N1 + 200 ke titik tempel kampus + 20 = 545 m
        $this->assertTrue($rute['tersedia']);
        $this->assertEqualsWithDelta(100, $rute['jarak_ke_jalan_m'], 1);
        $this->assertEqualsWithDelta(545, $rute['panjang_m'], 3);
        $this->assertStringContainsString('100 m dari jalan', $rute['catatan']);
    }

    public function test_kos_dan_kampus_di_ruas_yang_sama(): void
    {
        $this->jaringanUji();
        $kos = $this->kos('Kos Dekat', -100, -30);

        $rute = app(RuteService::class)->untukKos($kos);

        // 10 m ke jalan + 100 m sepanjang ruas yang sama + 20 m ke kampus
        $this->assertEqualsWithDelta(130, $rute['panjang_m'], 2);
        $this->assertSame(2, $rute['durasi_menit']);
        $this->assertCount(4, $rute['geometri']['coordinates']);
    }

    public function test_rute_yang_memutar_jauh_tetap_ditemukan(): void
    {
        // Jalan dekat kampus dan jalan dekat kos hanya tersambung lewat utara sejauh 3 km,
        // di luar area pencarian awal (sekitar 1,1 km). Rute harus dihitung ulang dengan seluruh jaringan.
        $this->ruas(1, [-20, -100], 2, [-20, 100], 'Jalan Kampus');
        $this->ruas(3, [320, -100], 4, [320, 100], 'Jalan Kos');
        $this->ruas(2, [-20, 100], 5, [-20, 3000]);
        $this->ruas(5, [-20, 3000], 6, [320, 3000]);
        $this->ruas(6, [320, 3000], 4, [320, 100]);
        app(ImportJaringanJalan::class)->tandaiKomponenUtama();

        $rute = app(RuteService::class)->untukKos($this->kos('Kos Seberang', 300, 0));

        // 20 + 100 + 2900 + 340 + 2900 + 100 + 20 = 6380 m
        $this->assertTrue($rute['tersedia']);
        $this->assertEqualsWithDelta(6380, $rute['panjang_m'], 15);
    }

    public function test_tanpa_jaringan_jalan_rute_tidak_tersedia(): void
    {
        $rute = app(RuteService::class)->untukKos($this->kos('Kos A', 100, 100));

        $this->assertFalse($rute['tersedia']);
        $this->assertStringContainsString('jalan:import-osm', $rute['alasan']);
    }

    public function test_cache_dipakai_sampai_versi_jaringan_berubah(): void
    {
        $kos = $this->kos('Kos Timur', 210, 300);
        $service = app(RuteService::class);

        $this->assertFalse($service->untukKos($kos)['tersedia']);

        // Jaringan ditambahkan tanpa mengganti versi: hasil lama dari cache masih dipakai.
        $this->jaringanUji();
        $this->assertFalse($service->untukKos($kos)['tersedia']);

        // Import menandai versi baru, cache lama otomatis tidak dipakai.
        Cache::forever(ImportJaringanJalan::KUNCI_VERSI, 'baru');
        $this->assertTrue($service->untukKos($kos)['tersedia']);
    }

    public function test_api_rute_untuk_kos_aktif(): void
    {
        $this->jaringanUji();
        $kos = $this->kos('Kos Timur', 210, 300);

        $this->getJson("/api/kos/{$kos->id}/rute")
            ->assertOk()
            ->assertJsonPath('tersedia', true)
            ->assertJsonPath('geometri.type', 'LineString')
            ->assertJsonStructure(['tersedia', 'panjang_m', 'durasi_menit', 'jarak_ke_jalan_m', 'catatan', 'geometri' => ['type', 'coordinates']]);
    }

    public function test_api_rute_kos_draft_atau_tidak_ada_404(): void
    {
        $this->jaringanUji();
        $draft = $this->kos('Kos Draft', 210, 300, StatusKos::Draft);

        $this->getJson("/api/kos/{$draft->id}/rute")->assertNotFound();
        $this->getJson('/api/kos/99999/rute')->assertNotFound();
    }

    public function test_perintah_rute_cek_melaporkan_semua_kos(): void
    {
        $this->jaringanUji();
        $this->kos('Kos Timur', 210, 300);
        $this->kos('Kos Barat', -100, 205);
        $this->kos('Kos Draft', -100, -30, StatusKos::Draft);

        $this->artisan('rute:cek')
            ->expectsOutputToContain('Berhasil dirutekan: 2 dari 2 kos.')
            ->expectsOutputToContain('1 kos berjarak lebih dari 50 m')
            ->assertSuccessful();

        $this->artisan('rute:cek --semua')
            ->expectsOutputToContain('Berhasil dirutekan: 3 dari 3 kos.')
            ->assertSuccessful();
    }

    public function test_perintah_rute_cek_gagal_bila_jaringan_belum_ada(): void
    {
        $this->kos('Kos Timur', 210, 300);

        $this->artisan('rute:cek')
            ->expectsOutputToContain('1 kos tidak bisa dirutekan.')
            ->assertFailed();
    }
}