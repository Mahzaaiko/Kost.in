<?php

namespace Tests\Feature\Routing;

use App\Services\Routing\ImportJaringanJalan;
use App\Support\Koordinat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class ImportJalanOsmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Storage::fake('local');
    }

    /** Titik (x ke timur, y ke utara, meter dari kampus) dalam format geometry Overpass. */
    private function g(float $x, float $y): array
    {
        $k = Koordinat::kampus();

        return ['lat' => $k->lat + $y / 110_580, 'lon' => $k->lng + $x / 110_584];
    }

    private function way(int $id, array $tags, array $nodes): array
    {
        return [
            'type' => 'way', 'id' => $id, 'tags' => $tags,
            'nodes' => array_keys($nodes),
            'geometry' => array_map(fn ($xy) => $this->g(...$xy), array_values($nodes)),
        ];
    }

    /**
     * Jalan A (barat-timur) dan Gang B (utara-selatan) bersilangan di node 2.
     * Gang C tersambung di ujung node 3. Footway D terpisah. Tol dan jalan foot=no dibuang.
     */
    /** @return list<array> */
    private function elemenUji(): array
    {
        return [
            $this->way(100, ['highway' => 'residential', 'name' => 'Jalan A'], [1 => [-100, 0], 2 => [0, 0], 3 => [100, 0]]),
            $this->way(200, ['highway' => 'footway', 'name' => 'Gang B'], [4 => [0, -100], 2 => [0, 0], 5 => [0, 100]]),
            $this->way(300, ['highway' => 'motorway', 'name' => 'Tol'], [6 => [-100, 50], 7 => [100, 50]]),
            $this->way(400, ['highway' => 'path', 'foot' => 'no'], [8 => [-100, 80], 9 => [100, 80]]),
            $this->way(500, ['highway' => 'service', 'access' => 'no', 'foot' => 'yes', 'name' => 'Gang C'], [3 => [100, 0], 10 => [100, -80]]),
            $this->way(600, ['highway' => 'footway'], [11 => [500, 500], 12 => [520, 500]]),
            ['type' => 'node', 'id' => 99, 'lat' => 0, 'lon' => 0],
        ];
    }

    private function fakeOverpass(): void
    {
        Http::fake(['*' => Http::response(['elements' => $this->elemenUji()])]);
    }

    private function tambahRuas(string $sumber, int $source, int $target): void
    {
        DB::insert(
            "INSERT INTO ruas_jalan (sumber, jenis, source, target, panjang_m, geom)
             VALUES (?, 'footway', ?, ?, 10, ST_GeomFromText('LINESTRING(106.80 -6.58, 106.8001 -6.58)', 4326))",
            [$sumber, $source, $target],
        );
    }

    public function test_tile_hanya_yang_bersinggungan_dengan_lingkaran(): void
    {
        $service = new ImportJaringanJalan;
        $kampus = Koordinat::kampus();

        // Radius 15 km dengan tile 3 km: kotak 10 x 10, pojok yang di luar lingkaran dilewati.
        $tiles = $service->tiles($kampus, 15, 3);
        $this->assertLessThan(100, count($tiles));
        $this->assertGreaterThan(70, count($tiles));

        // Semua tile berukuran sekitar 3 km dan titik kampus masuk salah satunya.
        $berisiKampus = array_filter($tiles, fn ($t) => $t[0] <= $kampus->lat && $kampus->lat <= $t[2] && $t[1] <= $kampus->lng && $kampus->lng <= $t[3]);
        $this->assertCount(1, $berisiKampus);
        $this->assertEqualsWithDelta(3 / 111.32, $tiles[0][2] - $tiles[0][0], 1e-5);
    }

    public function test_filter_jalan_pejalan_kaki(): void
    {
        $service = new ImportJaringanJalan;

        $this->assertTrue($service->bolehDilalui(['highway' => 'residential']));
        $this->assertTrue($service->bolehDilalui(['highway' => 'service', 'access' => 'private']));
        $this->assertTrue($service->bolehDilalui(['highway' => 'service', 'access' => 'no', 'foot' => 'yes']));
        $this->assertFalse($service->bolehDilalui(['highway' => 'motorway']));
        $this->assertFalse($service->bolehDilalui(['highway' => 'path', 'foot' => 'no']));
        $this->assertFalse($service->bolehDilalui(['highway' => 'track', 'access' => 'no']));
        $this->assertFalse($service->bolehDilalui(['building' => 'yes']));
    }

    public function test_import_memotong_jalan_di_persimpangan_dan_menandai_jaringan_utama(): void
    {
        $this->fakeOverpass();
        $this->tambahRuas('osm', 900, 901); // data OSM lama, harus terganti
        $this->tambahRuas('survei', 950, 951); // jalan tikus hasil survei, harus tetap ada

        $this->artisan('jalan:import-osm --radius=1')
            ->expectsOutputToContain('Memotong 4 jalan di setiap persimpangan')
            ->expectsOutputToContain('Mencari jaringan jalan utama')
            ->expectsOutputToContain('Selesai')
            ->assertSuccessful();

        Http::assertSent(fn (Request $r) => str_contains($r['data'], 'out body geom') && str_contains($r['data'], 'motorway'));

        $osm = DB::table('ruas_jalan')->where('sumber', 'osm')->orderBy('osm_way_id')->orderBy('source')->get();

        // Jalan A dan Gang B masing-masing terpotong 2 di node 2, ditambah Gang C dan footway D = 6 ruas.
        $this->assertSame([100, 100, 200, 200, 500, 600], $osm->pluck('osm_way_id')->map(fn ($v) => (int) $v)->all());
        $this->assertEqualsCanonicalizing(
            ['1-2', '2-3', '4-2', '2-5', '3-10', '11-12'],
            $osm->map(fn ($r) => $r->source.'-'.$r->target)->all(),
        );
        $this->assertSame(0, DB::table('ruas_jalan')->where('source', 900)->count());
        $this->assertSame(1, DB::table('ruas_jalan')->where('sumber', 'survei')->count());

        // Panjang dalam meter dari PostGIS.
        $this->assertEqualsWithDelta(100, (float) $osm->firstWhere('source', 1)->panjang_m, 1);
        $this->assertSame('Jalan A', $osm->firstWhere('source', 1)->nama);

        // Footway D terpisah dari jaringan utama.
        $this->assertSame([false], $osm->where('osm_way_id', 600)->pluck('utama')->map(fn ($v) => (bool) $v)->values()->all());
        $this->assertSame(5, $osm->where('utama', true)->count());
        $this->assertNotNull(Cache::get(ImportJaringanJalan::KUNCI_VERSI));

        // Setelah import berhasil, simpanan tile sementara dihapus.
        $this->assertFalse(Storage::disk('local')->exists(ImportJaringanJalan::FOLDER_TILE));
    }

    public function test_dry_run_tidak_mengubah_tabel(): void
    {
        $this->fakeOverpass();
        $this->tambahRuas('osm', 900, 901);

        $this->artisan('jalan:import-osm --radius=1 --dry-run')
            ->expectsOutputToContain('Dry run')
            ->assertSuccessful();

        $this->assertSame(1, DB::table('ruas_jalan')->count());
        $this->assertNull(Cache::get(ImportJaringanJalan::KUNCI_VERSI));

        // Tile hasil dry run disimpan, jadi import sungguhan berikutnya tidak perlu mengunduh ulang.
        $this->assertCount(1, Storage::disk('local')->files(ImportJaringanJalan::FOLDER_TILE));
        $this->artisan('jalan:import-osm --radius=1')
            ->expectsOutputToContain('1 dari simpanan')
            ->assertSuccessful();
        Http::assertSentCount(1);
        $this->assertSame(6, DB::table('ruas_jalan')->count());
    }

    public function test_jalan_yang_muncul_di_beberapa_tile_hanya_disimpan_sekali(): void
    {
        $this->fakeOverpass();

        $this->artisan('jalan:import-osm --radius=4')->assertSuccessful();

        $jumlahTile = count((new ImportJaringanJalan)->tiles(Koordinat::kampus(), 4, 3));
        $this->assertGreaterThan(1, $jumlahTile);
        Http::assertSentCount($jumlahTile);
        $this->assertSame(6, DB::table('ruas_jalan')->count());
    }

    public function test_tile_gagal_dicoba_ulang_di_akhir_lalu_import_berhasil(): void
    {
        // Tile pertama gagal di 4 permintaan pertama (1 percobaan + 3 ulangan bawaan), lalu berhasil.
        $panggilan = 0;
        Http::fake(function () use (&$panggilan) {
            $panggilan++;

            return $panggilan <= 4
                ? Http::response('Bad Gateway', 502)
                : Http::response(['elements' => $this->elemenUji()]);
        });

        $this->artisan('jalan:import-osm --radius=4')
            ->expectsOutputToContain('Selesai')
            ->assertSuccessful();

        Sleep::assertSlept(fn ($durasi) => $durasi->totalSeconds == ImportJaringanJalan::JEDA_COBA_ULANG_DETIK);
        $this->assertSame(6, DB::table('ruas_jalan')->count());
    }

    public function test_overpass_tetap_gagal_data_lama_tidak_berubah_dan_tile_berhasil_tersimpan(): void
    {
        // Hanya tile yang memuat titik kampus yang selalu gagal; tile lain berhasil.
        $import = new ImportJaringanJalan;
        $kampus = Koordinat::kampus();
        $tileKampus = collect($import->tiles($kampus, 4, 3))
            ->first(fn ($t) => $t[0] <= $kampus->lat && $kampus->lat <= $t[2] && $t[1] <= $kampus->lng && $kampus->lng <= $t[3]);
        $queryGagal = $import->queryOverpass($tileKampus);

        $pulih = false;
        Http::fake(function (Request $r) use (&$pulih, $queryGagal) {
            if (! $pulih && $r['data'] === $queryGagal) {
                return Http::response('Gateway Timeout', 504);
            }

            return Http::response(['elements' => $this->elemenUji()]);
        });
        $this->tambahRuas('osm', 900, 901);

        $jumlahTile = count($import->tiles($kampus, 4, 3));
        $this->artisan('jalan:import-osm --radius=4')
            ->expectsOutputToContain("1 dari {$jumlahTile} area gagal diambil dari Overpass (HTTP 504)")
            ->expectsOutputToContain('Tabel ruas_jalan tidak diubah.')
            ->assertFailed();

        $this->assertSame(1, DB::table('ruas_jalan')->where('source', 900)->count());
        $this->assertCount($jumlahTile - 1, Storage::disk('local')->files(ImportJaringanJalan::FOLDER_TILE));

        // Dijalankan ulang setelah Overpass pulih: hanya tile yang tadi gagal yang diunduh.
        $pulih = true;
        $terkirimSebelumnya = count(Http::recorded());
        $this->artisan('jalan:import-osm --radius=4')
            ->expectsOutputToContain(($jumlahTile - 1).' dari simpanan')
            ->assertSuccessful();
        $this->assertCount($terkirimSebelumnya + 1, Http::recorded());
        $this->assertSame(0, DB::table('ruas_jalan')->where('source', 900)->count());
    }

    public function test_opsi_segarkan_mengabaikan_simpanan(): void
    {
        $this->fakeOverpass();
        $this->artisan('jalan:import-osm --radius=1 --dry-run')->assertSuccessful();

        $this->artisan('jalan:import-osm --radius=1 --segarkan')->assertSuccessful();

        Http::assertSentCount(2);
    }

    public function test_koneksi_putus_ditangani_tanpa_error_mentah(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28'));

        $this->artisan('jalan:import-osm --radius=1')
            ->expectsOutputToContain('koneksi gagal atau habis waktu')
            ->assertFailed();
    }

    public function test_radius_tidak_valid_ditolak(): void
    {
        Http::fake();

        $this->artisan('jalan:import-osm --radius=50')->assertFailed();
        Http::assertNothingSent();
    }
}