<?php

namespace Tests\Feature\Routing;

use App\Enums\StatusKos;
use App\Enums\TipeKos;
use App\Models\Kos;
use App\Models\RekamanJalan;
use App\Models\User;
use App\Services\Routing\ImportJaringanJalan;
use App\Services\Routing\JaringanSurvei;
use App\Services\Routing\RuteService;
use App\Support\Koordinat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Tests\TestCase;

/**
 * Jaringan uji sama dengan RuteTest (meter dari kampus, x ke timur, y ke utara):
 *
 *   N5 (-200,400) ──────── N6 (0,400) ──────── N4 (200,400)
 *        │          ┊ gang survei x = -50          │
 *        │          ┊                              │
 *   N1 (-200,-20) ──┴───── N2 (50,-20) ────── N3 (200,-20)
 *                    kampus (0,0)
 */
class JaringanSurveiTest extends TestCase
{
    use RefreshDatabase;

    private const M_PER_DERAJAT_LAT = 110_580;

    private const M_PER_DERAJAT_LNG = 110_584;

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
        $this->ruas(5, [-200, 400], 6, [0, 400], 'Jalan Atas Barat');
        $this->ruas(6, [0, 400], 4, [200, 400], 'Jalan Atas Timur');

        app(ImportJaringanJalan::class)->tandaiKomponenUtama();
    }

    private function kos(string $nama, float $x, float $y): Kos
    {
        return Kos::create([
            'nama' => $nama, 'alamat' => 'Jl. Uji', 'tipe' => TipeKos::Putri, 'harga_per_bulan' => 800_000,
            'periode_minimal_bulan' => 1, 'status' => StatusKos::Aktif, 'lokasi' => $this->titik($x, $y),
        ]);
    }

    /**
     * Isi GPX seperti dari aplikasi GPS: titik tiap 10 m dengan goyangan kecil ke samping.
     *
     * @param  list<array{0: float, 1: float}>  $jalur  titik belok dalam meter
     */
    private function gpx(array $jalur, float $goyang = 1.0): string
    {
        $titik = [];
        for ($i = 0; $i < count($jalur) - 1; $i++) {
            [$x1, $y1] = $jalur[$i];
            [$x2, $y2] = $jalur[$i + 1];
            $langkah = max(1, (int) ceil(hypot($x2 - $x1, $y2 - $y1) / 10));
            for ($j = 0; $j < $langkah; $j++) {
                $t = $j / $langkah;
                $titik[] = [$x1 + ($x2 - $x1) * $t + (($j % 2) ? $goyang : -$goyang), $y1 + ($y2 - $y1) * $t];
            }
        }
        $titik[] = end($jalur);

        $trkpt = implode("\n", array_map(function ($p) {
            $k = $this->titik(...$p);

            return sprintf('      <trkpt lat="%.7F" lon="%.7F"><ele>250</ele></trkpt>', $k->lat, $k->lng);
        }, $titik));

        return <<<GPX
        <?xml version="1.0" encoding="UTF-8"?>
        <gpx version="1.1" creator="GPS Logger" xmlns="http://www.topografix.com/GPX/1/1">
          <trk><name>Rekaman</name><trkseg>
        {$trkpt}
          </trkseg></trk>
        </gpx>
        GPX;
    }

    private function unggah(string $isi, string $namaFile = 'gang.gpx', array $data = [])
    {
        return $this->post('/admin/jaringan-jalan', $data + [
            'berkas' => UploadedFile::fake()->createWithContent($namaFile, $isi),
        ]);
    }

    /** Gang lurus x = -50 dari 6 m di utara Jalan Atas sampai 6 m di selatan Jalan Bawah (GPS kelewatan sedikit). */
    private function gangTengah(): string
    {
        return $this->gpx([[-50, 406], [-50, -26]]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    public function test_gang_tembus_memperpendek_rute_dan_osm_saja_tidak_berubah(): void
    {
        $this->jaringanUji();
        $kos = $this->kos('Kos Atas', -10, 380);
        $rute = app(RuteService::class);

        // Sebelum survei: 20 m ke Jalan Atas, memutar lewat N5 dan N1 = 20 + 190 + 420 + 200 + 20 = 850 m
        $this->assertEqualsWithDelta(850, $rute->untukKos($kos)['panjang_m'], 3);

        $this->unggah($this->gangTengah(), 'gang.gpx', ['nama' => 'Gang Tengah', 'catatan' => 'Uji'])
            ->assertRedirect('/admin/jaringan-jalan')
            ->assertSessionHas('success', fn ($p) => str_contains($p, 'Gang Tengah tersimpan') && str_contains($p, 'kedua ujungnya tersambung'));

        $rekaman = RekamanJalan::sole();
        $this->assertSame(2, $rekaman->ujung_tersambung);
        $this->assertSame('Uji', $rekaman->catatan);
        $this->assertLessThan(10, $rekaman->jumlah_titik, 'Titik GPS yang goyang dirapikan');

        // Gang 1 ruas, kedua ujung menempel di tengah ruas sehingga ada 2 x 2 ruas sambungan.
        $this->assertSame(1, DB::table('ruas_jalan')->where('sumber', 'survei')->count());
        $this->assertSame(4, DB::table('ruas_jalan')->where('sumber', 'sambungan')->count());
        $gang = DB::table('ruas_jalan')->where('sumber', 'survei')->first();
        $this->assertEqualsWithDelta(420, (float) $gang->panjang_m, 3, 'Ujung gang dipindah tepat ke jalan');
        $this->assertTrue((bool) $gang->utama);

        // Sesudah survei: 20 + 40 + 420 + 50 + 20 = 550 m. Rute OSM saja tetap 850 m.
        $this->assertEqualsWithDelta(550, $rute->untukKos($kos)['panjang_m'], 4);
        $this->assertEqualsWithDelta(850, $rute->untukKos($kos, false)['panjang_m'], 3);

        // API Jelajah ikut memakai gang tanpa kolom tambahan.
        $this->getJson("/api/kos/{$kos->id}/rute")
            ->assertOk()
            ->assertJsonPath('tersedia', true)
            ->assertJsonMissingPath('survei');
        $this->assertEqualsWithDelta(550, $this->getJson("/api/kos/{$kos->id}/rute")->json('panjang_m'), 4);
    }

    public function test_gang_buntu_boleh_dan_kos_di_ujungnya_menempel_ke_gang(): void
    {
        $this->jaringanUji();
        $kos = $this->kos('Kos Ujung Gang', 105, 150);
        $rute = app(RuteService::class);

        // Sebelum survei kos 95 m dari Jalan Timur, jadi ada catatan.
        $this->assertNotNull($rute->untukKos($kos)['catatan']);

        // Gang dari Jalan Bawah Timur (x = 100) ke utara sampai depan kos, ujung atas tidak menyentuh jalan.
        $this->unggah($this->gpx([[100, -22], [100, 150]]))
            ->assertSessionHas('success', fn ($p) => str_contains($p, 'gang buntu'));

        $this->assertSame(1, RekamanJalan::sole()->ujung_tersambung);

        // 5 m ke gang + 170 m gang + 50 m ke N2 + 50 m ke titik tempel kampus + 20 m = 295 m
        $sesudah = $rute->untukKos($kos);
        $this->assertEqualsWithDelta(5, $sesudah['jarak_ke_jalan_m'], 1);
        $this->assertNull($sesudah['catatan']);
        $this->assertEqualsWithDelta(295, $sesudah['panjang_m'], 4);
    }

    public function test_gang_dekat_kampus_tidak_membuat_rute_lain_lebih_panjang(): void
    {
        $this->jaringanUji();
        $kos = $this->kos('Kos Bawah Timur', 100, -30);

        // Gang buntu miring dari Jalan Bawah (-30,-20) ke (30,60) lewat 12 m dari kampus, lebih dekat
        // dari Jalan Bawah (20 m). Kampus jadi menempel ke gang, dan di jaringan lengkap rute kos ini
        // memutar lewat gang (sekitar 186 m). Rute OSM 130 m tetap dipakai karena lebih pendek.
        $this->unggah($this->gpx([[-30, -20], [30, 60]], 0))->assertSessionHasNoErrors();

        $this->assertEqualsWithDelta(130, app(RuteService::class)->untukKos($kos)['panjang_m'], 2);
        $this->getJson("/admin/jaringan-jalan/bandingkan/{$kos->id}")->assertJsonPath('selisih_m', 0);
    }

    public function test_ujung_dekat_simpul_memakai_simpul_yang_ada(): void
    {
        $this->jaringanUji();

        // Mulai 1 m dari N6 (0,400), berakhir di tengah Jalan Bawah: hanya ujung bawah yang butuh sambungan.
        $this->unggah($this->gpx([[1, 401], [1, -20]]))->assertSessionHasNoErrors();

        $gang = DB::table('ruas_jalan')->where('sumber', 'survei')->first();
        $this->assertSame(6, (int) $gang->source);
        $this->assertSame(2, DB::table('ruas_jalan')->where('sumber', 'sambungan')->count());
    }

    public function test_gang_boleh_menyambung_ke_gang_survei_lain(): void
    {
        $this->jaringanUji();
        $this->unggah($this->gangTengah(), 'gang-1.gpx', ['nama' => 'Gang Satu']);

        // Gang kedua dari tengah Gang Satu (-50, 200) ke barat sampai Jalan Barat (-200, 200).
        $this->unggah($this->gpx([[-50, 200], [-200, 200]]), 'gang-2.gpx', ['nama' => 'Gang Dua'])
            ->assertSessionHas('success', fn ($p) => str_contains($p, 'kedua ujungnya'));

        $dua = RekamanJalan::where('nama', 'Gang Dua')->sole();
        $sambunganKeGang = DB::table('ruas_jalan as s')
            ->join('ruas_jalan as g', fn ($j) => $j->whereColumn('g.source', 's.source')->orWhereColumn('g.target', 's.target'))
            ->where('s.rekaman_id', $dua->id)->where('s.sumber', 'sambungan')->where('g.sumber', 'survei')->where('g.nama', 'Gang Satu')
            ->exists();
        $this->assertTrue($sambunganKeGang, 'Sambungan Gang Dua dipotong dari Gang Satu');
        $this->assertSame(2, DB::table('ruas_jalan')->where('sumber', 'survei')->count());
        $this->assertSame(4, DB::table('ruas_jalan')->where('sumber', 'sambungan')->where('rekaman_id', $dua->id)->count());
    }

    public function test_hapus_gang_mengembalikan_rute_dan_menyambung_ulang_gang_lain(): void
    {
        $this->jaringanUji();
        $kos = $this->kos('Kos Atas', -10, 380);
        $this->unggah($this->gangTengah(), 'gang-1.gpx', ['nama' => 'Gang Satu']);
        $this->unggah($this->gpx([[-50, 200], [-200, 200]]), 'gang-2.gpx', ['nama' => 'Gang Dua']);
        $versi = Cache::get(ImportJaringanJalan::KUNCI_VERSI);

        $satu = RekamanJalan::where('nama', 'Gang Satu')->sole();
        $this->delete("/admin/jaringan-jalan/{$satu->id}")
            ->assertRedirect('/admin/jaringan-jalan')
            ->assertSessionHas('success', 'Gang Satu dihapus. Rute kos dihitung ulang tanpa gang ini.');

        $this->assertNotSame($versi, Cache::get(ImportJaringanJalan::KUNCI_VERSI));
        $this->assertEqualsWithDelta(850, app(RuteService::class)->untukKos($kos)['panjang_m'], 3);

        // Gang Dua tinggal menyambung ke Jalan Barat (gang buntu), tanpa sisa potongan Gang Satu.
        $dua = RekamanJalan::where('nama', 'Gang Dua')->sole();
        $this->assertSame(1, $dua->ujung_tersambung);
        $this->assertSame(0, DB::table('ruas_jalan')->where('nama', 'Gang Satu')->count());
        $this->assertSame(1, DB::table('ruas_jalan')->where('sumber', 'survei')->count());
        $this->assertSame(2, DB::table('ruas_jalan')->where('sumber', 'sambungan')->count());
    }

    public function test_rekaman_ditolak_dengan_pesan_jelas(): void
    {
        $this->jaringanUji();

        // Kedua ujung jauh dari jalan.
        $this->unggah($this->gpx([[-120, 100], [-120, 300]]))
            ->assertSessionHasErrors(['berkas' => 'Kedua ujung rekaman lebih dari 15 m dari jalan yang sudah ada, jadi gang ini tidak bisa disambungkan. Rekam mulai dari jalan yang sudah ada di peta sampai ke jalan lain atau ujung gang.']);

        // Terlalu pendek.
        $this->unggah($this->gpx([[-50, 400], [-50, 398]], 0))
            ->assertSessionHasErrors(['berkas' => 'Rekaman terlalu pendek (2.0 m). Pastikan GPS merekam sepanjang gang.']);

        // Di luar radius 15 km (sekitar 20 km ke utara).
        $this->unggah($this->gpx([[0, 20_000], [0, 20_100]]))
            ->assertSessionHasErrors('berkas');

        // Bukan GPX/GeoJSON, ekstensi salah, dan waypoint saja.
        $this->unggah('bukan xml', 'gang.gpx')->assertSessionHasErrors(['berkas' => 'File GPX tidak bisa dibaca. Ekspor ulang rekaman dari aplikasi GPS sebagai GPX.']);
        $this->unggah('isi', 'gang.kml')->assertSessionHasErrors(['berkas' => 'File harus berformat .gpx, .geojson, atau .json.']);
        $this->unggah('<gpx><wpt lat="-6.58" lon="106.8"/></gpx>')->assertSessionHasErrors(['berkas' => 'File GPX tidak berisi jalur (track). Pastikan yang diekspor adalah rekaman perjalanan, bukan titik tanda (waypoint).']);

        $this->assertSame(0, RekamanJalan::count());
        $this->assertSame(0, DB::table('ruas_jalan')->where('sumber', '<>', 'osm')->count());
    }

    public function test_geojson_satu_garis_diterima_lebih_dari_satu_ditolak(): void
    {
        $this->jaringanUji();
        $koordinat = fn (array $jalur) => array_map(fn ($p) => [$this->titik(...$p)->lng, $this->titik(...$p)->lat], $jalur);

        $satu = json_encode(['type' => 'FeatureCollection', 'features' => [
            ['type' => 'Feature', 'properties' => [], 'geometry' => ['type' => 'LineString', 'coordinates' => $koordinat([[-50, 400], [-50, 200], [-50, -20]])]],
        ]]);
        $this->unggah($satu, 'gang.geojson')->assertSessionHasNoErrors();
        $this->assertSame(2, RekamanJalan::sole()->ujung_tersambung);

        $dua = json_encode(['type' => 'MultiLineString', 'coordinates' => [$koordinat([[100, -20], [100, 100]])]]);
        $this->unggah($dua, 'gang-2.json')->assertSessionHasNoErrors();

        $banyak = json_encode(['type' => 'FeatureCollection', 'features' => [
            ['type' => 'Feature', 'geometry' => ['type' => 'LineString', 'coordinates' => $koordinat([[-150, 400], [-150, -20]])]],
            ['type' => 'Feature', 'geometry' => ['type' => 'LineString', 'coordinates' => $koordinat([[150, 400], [150, -20]])]],
        ]]);
        $this->unggah($banyak, 'banyak.geojson')
            ->assertSessionHasErrors(['berkas' => 'GeoJSON berisi lebih dari satu garis. Satu file hanya untuk satu gang, pisahkan setiap gang ke file sendiri.']);

        $this->assertSame(2, RekamanJalan::count());
    }

    public function test_import_ulang_osm_menyambung_ulang_gang(): void
    {
        Sleep::fake();
        Storage::fake('local');

        // Overpass mengembalikan satu jalan barat-timur (y = 0) dan satu jalan di utara (y = 200).
        $k = Koordinat::kampus();
        $g = fn ($x, $y) => ['lat' => $k->lat + $y / self::M_PER_DERAJAT_LAT, 'lon' => $k->lng + $x / self::M_PER_DERAJAT_LNG];
        $way = fn ($id, $nodes) => ['type' => 'way', 'id' => $id, 'tags' => ['highway' => 'residential'], 'nodes' => array_keys($nodes), 'geometry' => array_map(fn ($xy) => $g(...$xy), array_values($nodes))];
        Http::fake(['*' => Http::response(['elements' => [
            $way(100, [1 => [-300, 0], 2 => [300, 0]]),
            $way(200, [3 => [-300, 200], 4 => [300, 200]]),
            $way(300, [1 => [-300, 0], 3 => [-300, 200]]),
        ]])]);

        $this->artisan('jalan:import-osm --radius=1')->assertSuccessful();
        $this->unggah($this->gpx([[0, 0], [0, 200]]), 'gang.gpx', ['nama' => 'Gang Uji'])->assertSessionHasNoErrors();
        $idGangLama = DB::table('ruas_jalan')->where('sumber', 'survei')->value('id');

        $this->artisan('jalan:import-osm --radius=1')
            ->expectsOutputToContain('Menyambungkan ulang gang hasil survei')
            ->assertSuccessful();

        $gang = DB::table('ruas_jalan')->where('sumber', 'survei')->first();
        $this->assertNotSame($idGangLama, $gang->id);
        $this->assertTrue((bool) $gang->utama);
        $this->assertSame(4, DB::table('ruas_jalan')->where('sumber', 'sambungan')->count());
        $this->assertSame(2, RekamanJalan::sole()->ujung_tersambung);
    }

    public function test_halaman_jaringan_jalan_dan_pembanding(): void
    {
        $this->jaringanUji();
        $kos = $this->kos('Kos Atas', -10, 380);
        $this->unggah($this->gangTengah(), 'gang.gpx', ['nama' => 'Gang Tengah']);

        $this->get('/admin/jaringan-jalan')
            ->assertOk()
            ->assertSee('Unggah Rekaman Gang')
            ->assertSee('Gang Tengah')
            ->assertSee('Kos Atas')
            ->assertSee('Tabel Validasi Jarak')
            ->assertDontSee('Jaringan jalan OSM belum diimpor');

        $this->getJson("/admin/jaringan-jalan/bandingkan/{$kos->id}")
            ->assertOk()
            ->assertJsonPath('nama', 'Kos Atas')
            ->assertJsonPath('geometri_osm.type', 'LineString')
            ->assertJsonPath('geometri_survei.type', 'LineString')
            ->assertJson(fn ($json) => $json
                ->where('selisih_m', fn ($v) => abs($v - 300) <= 5)
                ->where('rasio_osm', fn ($v) => $v > 2.1 && $v < 2.3)
                ->where('rasio_survei', fn ($v) => $v > 1.3 && $v < 1.5)
                ->etc());

        $tanpaLokasi = Kos::create(['nama' => 'Tanpa Titik', 'status' => StatusKos::Draft]);
        $this->getJson("/admin/jaringan-jalan/bandingkan/{$tanpaLokasi->id}")->assertStatus(422);
    }

    public function test_ekspor_csv_untuk_laporan(): void
    {
        $this->jaringanUji();
        $this->kos('Kos Atas', -10, 380);
        $this->unggah($this->gangTengah());

        $csv = $this->get('/admin/jaringan-jalan/ekspor')->assertOk()->streamedContent();
        $baris = explode("\n", trim($csv));

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertSame('No;"Nama Kos";Status;"Garis Lurus (m)";"Rute OSM (m)";"Rute OSM + Survei (m)";"Selisih (m)";"Rasio OSM";"Rasio OSM + Survei"', substr($baris[0], 3));
        $this->assertCount(2, $baris);
        $this->assertMatchesRegularExpression('/^1;"Kos Atas";Aktif;3\d\d;8\d\d;5\d\d;(29|30)\d;2,\d\d;1,\d\d$/', $baris[1]);
    }

    public function test_halaman_tanpa_jaringan_osm_memberi_peringatan_dan_tamu_ditolak(): void
    {
        $this->get('/admin/jaringan-jalan')->assertOk()->assertSee('Jaringan jalan OSM belum diimpor');

        Auth::logout();
        $this->get('/admin/jaringan-jalan')->assertRedirect('/admin/login');
        $this->post('/admin/jaringan-jalan')->assertRedirect('/admin/login');
    }

    public function test_layanan_bisa_dipakai_langsung(): void
    {
        $this->jaringanUji();
        $titik = array_map(fn ($p) => [$this->titik(...$p)->lng, $this->titik(...$p)->lat], [[-50, 400], [-50, -20]]);

        $rekaman = app(JaringanSurvei::class)->tambah($titik, 'manual.geojson', 'Gang Manual');

        $this->assertSame('Gang Manual', $rekaman->namaTampil());
        $this->assertEqualsWithDelta(420, $rekaman->panjang_m, 2);
    }
}