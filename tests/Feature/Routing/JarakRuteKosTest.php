<?php

namespace Tests\Feature\Routing;

use App\Enums\StatusKos;
use App\Enums\TipeKos;
use App\Models\Kos;
use App\Models\User;
use App\Services\Routing\ImportJaringanJalan;
use App\Services\Routing\JarakRuteKos;
use App\Support\Koordinat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Jarak rute jalan kaki sebagai jarak acuan pencarian dan SAW.
 *
 * Jaringan uji sama dengan JaringanSurveiTest (meter dari kampus, x ke timur, y ke utara):
 *
 *   N5 (-200,400) ──────── N6 (0,400) ──────── N4 (200,400)
 *        │                                         │
 *   N1 (-200,-20) ──────── N2 (50,-20) ────── N3 (200,-20)
 *                    kampus (0,0)
 *
 * Tiga kos dengan harga dan fasilitas sama:
 * - Kos Timur (150,-60): garis lurus 162 m, rute 40 + 150 + 20 = 210 m
 * - Kos Tengah (0,300): garis lurus 300 m, rute 100 + 200 + 420 + 200 + 20 = 940 m
 * - Kos Atas (-10,380): garis lurus 380 m, rute 20 + 190 + 420 + 200 + 20 = 850 m
 * Garis lurus mengurutkan Timur, Tengah, Atas. Rute mengurutkan Timur, Atas, Tengah.
 */
class JarakRuteKosTest extends TestCase
{
    use RefreshDatabase;

    private const M_PER_DERAJAT_LAT = 110_580;

    private const M_PER_DERAJAT_LNG = 110_584;

    private function titik(float $x, float $y): Koordinat
    {
        $k = Koordinat::kampus();

        return new Koordinat(lat: $k->lat + $y / self::M_PER_DERAJAT_LAT, lng: $k->lng + $x / self::M_PER_DERAJAT_LNG);
    }

    private function ruas(int $source, array $dari, int $target, array $ke): void
    {
        $a = $this->titik(...$dari);
        $b = $this->titik(...$ke);
        DB::insert(
            "INSERT INTO ruas_jalan (sumber, nama, jenis, source, target, panjang_m, geom)
             SELECT 'osm', 'Jalan Uji', 'residential', ?, ?, ST_Length(g::geography), g
             FROM (SELECT ST_SetSRID(ST_MakeLine(ST_MakePoint(?, ?), ST_MakePoint(?, ?)), 4326) AS g) t",
            [$source, $target, $a->lng, $a->lat, $b->lng, $b->lat],
        );
    }

    private function jaringanUji(): void
    {
        $this->ruas(1, [-200, -20], 2, [50, -20]);
        $this->ruas(2, [50, -20], 3, [200, -20]);
        $this->ruas(3, [200, -20], 4, [200, 400]);
        $this->ruas(1, [-200, -20], 5, [-200, 400]);
        $this->ruas(5, [-200, 400], 6, [0, 400]);
        $this->ruas(6, [0, 400], 4, [200, 400]);

        $import = app(ImportJaringanJalan::class);
        $import->tandaiKomponenUtama();
        $import->gantiVersi();
    }

    private function kos(string $nama, float $x, float $y): Kos
    {
        return Kos::create([
            'nama' => $nama, 'alamat' => 'Jl. Uji', 'tipe' => TipeKos::Putri, 'harga_per_bulan' => 800_000,
            'periode_minimal_bulan' => 1, 'status' => StatusKos::Aktif, 'lokasi' => $this->titik($x, $y),
        ]);
    }

    private function tigaKos(): void
    {
        $this->jaringanUji();
        $this->kos('Kos Timur', 150, -60);
        $this->kos('Kos Tengah', 0, 300);
        $this->kos('Kos Atas', -10, 380);
    }

    /** GPX lurus dengan titik tiap 10 m. */
    private function gpx(array $dari, array $ke): string
    {
        $langkah = (int) ceil(hypot($ke[0] - $dari[0], $ke[1] - $dari[1]) / 10);
        $trkpt = '';
        for ($i = 0; $i <= $langkah; $i++) {
            $t = $i / $langkah;
            $k = $this->titik($dari[0] + ($ke[0] - $dari[0]) * $t, $dari[1] + ($ke[1] - $dari[1]) * $t);
            $trkpt .= sprintf('<trkpt lat="%.7F" lon="%.7F"></trkpt>', $k->lat, $k->lng);
        }

        return '<?xml version="1.0"?><gpx version="1.1" xmlns="http://www.topografix.com/GPX/1/1"><trk><trkseg>'
            .$trkpt.'</trkseg></trk></gpx>';
    }

    private function nama(array $data): array
    {
        return array_column($data, 'nama');
    }

    public function test_urut_jarak_dan_rekomendasi_memakai_rute_bukan_garis_lurus(): void
    {
        $this->tigaKos();

        $jarak = $this->getJson('/api/kos?urut=jarak')->assertOk();
        $this->assertSame(['Kos Timur', 'Kos Atas', 'Kos Tengah'], $this->nama($jarak->json('data')));
        $jarak->assertJsonPath('meta.kriteria_jarak', 'rute_jalan_kaki');

        // Harga dan fasilitas sama, jadi skor SAW hanya ditentukan jarak rute.
        $rekomendasi = $this->getJson('/api/kos')->json('data');
        $this->assertSame(['Kos Timur', 'Kos Atas', 'Kos Tengah'], $this->nama($rekomendasi));
        $this->assertEquals(1.0, $rekomendasi[0]['skor']);
        $this->assertEquals(0.5, $rekomendasi[2]['skor'], 'Jarak terjauh dapat 0 dari bobot jarak 0,5');
    }

    public function test_api_mengembalikan_jarak_rute_dan_waktu_jalan(): void
    {
        $this->tigaKos();

        $data = collect($this->getJson('/api/kos?urut=jarak')->json('data'))->keyBy('nama');

        $timur = $data['Kos Timur'];
        $this->assertEqualsWithDelta(162, $timur['jarak_m'], 2);
        $this->assertEqualsWithDelta(210, $timur['jarak_rute_m'], 3);
        $this->assertSame(3, $timur['waktu_jalan_menit']); // 210 m / 80 m per menit

        $this->assertEqualsWithDelta(850, $data['Kos Atas']['jarak_rute_m'], 3);
        $this->assertSame(11, $data['Kos Atas']['waktu_jalan_menit']);
        $this->assertSame(12, $data['Kos Tengah']['waktu_jalan_menit']);
    }

    public function test_slider_jarak_menyaring_dengan_jarak_rute(): void
    {
        $this->tigaKos();

        // Ketiga kos ada dalam 400 m garis lurus, tapi hanya Kos Timur yang rutenya <= 400 m.
        $this->assertSame(['Kos Timur'], $this->nama($this->getJson('/api/kos?jarak_max_km=0.4')->json('data')));
        $this->assertSame(['Kos Timur', 'Kos Atas'], $this->nama($this->getJson('/api/kos?jarak_max_km=0.9&urut=jarak')->json('data')));
        $this->assertCount(3, $this->getJson('/api/kos?jarak_max_km=1')->json('data'));
    }

    public function test_area_slider_mengikuti_jalan(): void
    {
        $this->jaringanUji();

        $respon = $this->getJson('/api/peta/jangkauan?jarak_km=0.54')->assertOk()
            ->assertJsonPath('tersedia', true)
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.jarak_m', 500)
            ->assertJsonPath('features.0.properties.menit', 7);

        $area = json_encode($respon->json('features.0.geometry'));
        $di = fn (float $x, float $y) => (bool) DB::scalar(
            'SELECT ST_Contains(ST_GeomFromGeoJSON(?), ST_SetSRID(ST_MakePoint(?, ?), 4326))',
            [$area, $this->titik($x, $y)->lng, $this->titik($x, $y)->lat],
        );

        // 500 m lewat jalan: Jalan Bawah sampai x = 150 masuk, Jalan Atas (garis lurus 400 m) tidak.
        $this->assertTrue($di(150, -20));
        $this->assertFalse($di(0, 400));

        $this->getJson('/api/peta/jangkauan?jarak_km=0')->assertJsonValidationErrors('jarak_km');
        $this->getJson('/api/peta/jangkauan?jarak_km=11')->assertJsonValidationErrors('jarak_km');
        // Tanpa jarak_km tetap pita waktu seperti sebelumnya.
        $this->assertSame(20, $this->getJson('/api/peta/jangkauan')->json('features.0.properties.menit'));
    }

    public function test_perintah_menyiapkan_area_slider(): void
    {
        $this->jaringanUji();

        $this->artisan('peta:siapkan-jangkauan --maks=0.3')
            ->expectsOutputToContain('Area 0,1 sampai 0.3 km siap.')
            ->assertSuccessful();
    }

    public function test_unggah_gang_menghitung_ulang_jarak_rute_tanpa_menunggu_pencarian(): void
    {
        $this->tigaKos();
        app(JarakRuteKos::class)->perbaruiYangUsang();
        $this->assertEqualsWithDelta(850, Kos::where('nama', 'Kos Atas')->value('jarak_rute_m'), 3);

        // Gang tengah x = -50 dari Jalan Atas ke Jalan Bawah.
        $this->actingAs(User::factory()->create())
            ->post('/admin/jaringan-jalan', ['berkas' => UploadedFile::fake()->createWithContent('gang.gpx', $this->gpx([-50, 400], [-50, -20]))])
            ->assertSessionHasNoErrors();

        $versi = JarakRuteKos::versiSekarang();
        $this->assertSame(0, Kos::where('jarak_rute_versi', '!=', $versi)->count(), 'Semua kos sudah dihitung ulang');
        // Kos Atas: 20 + 40 + 420 + 50 + 20 = 550 m. Kos Tengah: 50 + 320 + 50 + 20 = 440 m.
        $this->assertEqualsWithDelta(550, Kos::where('nama', 'Kos Atas')->value('jarak_rute_m'), 4);
        $this->assertEqualsWithDelta(440, Kos::where('nama', 'Kos Tengah')->value('jarak_rute_m'), 4);
    }

    public function test_pindah_lokasi_mengosongkan_dan_menghitung_ulang_jarak_rute(): void
    {
        $this->tigaKos();
        $this->getJson('/api/kos')->assertOk();

        $kos = Kos::where('nama', 'Kos Atas')->sole();
        $this->assertNotNull($kos->jarak_rute_m);

        $kos->update(['lokasi' => $this->titik(150, -60)]);
        $this->assertNull($kos->fresh()->jarak_rute_m);
        $this->assertNull($kos->fresh()->jarak_rute_versi);

        $this->getJson('/api/kos')->assertOk();
        $this->assertEqualsWithDelta(210, $kos->fresh()->jarak_rute_m, 3);

        // Ubah kolom lain tidak membuang hasil hitungan.
        $kos->update(['harga_per_bulan' => 900_000]);
        $this->assertNotNull($kos->fresh()->jarak_rute_m);
    }

    public function test_tanpa_jaringan_jalan_kembali_ke_garis_lurus(): void
    {
        $this->kos('Kos Timur', 150, -60);
        $this->kos('Kos Atas', -10, 380);

        $data = $this->getJson('/api/kos?urut=jarak')->assertOk()->json('data');
        $this->assertSame(['Kos Timur', 'Kos Atas'], $this->nama($data));
        $this->assertNull($data[0]['jarak_rute_m']);
        $this->assertNull($data[0]['waktu_jalan_menit']);

        // Slider jarak kembali ke garis lurus: Kos Timur 162 m, Kos Atas 380 m.
        $this->assertSame(['Kos Timur'], $this->nama($this->getJson('/api/kos?jarak_max_km=0.2')->json('data')));
    }
}
