<?php

namespace Tests\Feature\Routing;

use App\Services\Routing\ImportJaringanJalan;
use App\Support\Koordinat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Jaringan uji (meter dari kampus, x ke timur, y ke utara). Jalan atas dan bawah hanya terhubung
 * lewat jembatan di x = 500, seperti dua sisi sungai:
 *
 *   N4 (-500,300) ──────── N6 (0,300) ──────── N3 (500,300)
 *                           ┊ gang survei            │ jembatan
 *   N1 (-500,-20) ──────── N5 (0,-20) ──────── N2 (500,-20)
 *                          kampus (0,0)
 *
 * Kecepatan 4,8 km/jam = 80 m/menit, kampus berjarak 20 m dari jalan bawah.
 */
class LayerPetaTest extends TestCase
{
    use RefreshDatabase;

    private const M_PER_DERAJAT_LAT = 110_580;

    private const M_PER_DERAJAT_LNG = 110_584;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'kostin.routing.kecepatan_jalan_kmh' => 4.8,
            'kostin.peta.jangkauan_menit' => [5, 10, 15, 20],
            'kostin.peta.jangkauan_lebar_m' => 30,
            'kostin.peta.radius_jalan_km' => 2,
        ]);
    }

    private function titik(float $x, float $y): Koordinat
    {
        $k = Koordinat::kampus();

        return new Koordinat(lat: $k->lat + $y / self::M_PER_DERAJAT_LAT, lng: $k->lng + $x / self::M_PER_DERAJAT_LNG);
    }

    private function ruas(int $source, array $dari, int $target, array $ke, string $sumber = 'osm', bool $terpecah = false): void
    {
        $a = $this->titik(...$dari);
        $b = $this->titik(...$ke);
        DB::insert(
            'INSERT INTO ruas_jalan (sumber, nama, jenis, source, target, panjang_m, terpecah, geom)
             SELECT ?, ?, ?, ?, ?, ST_Length(g::geography), ?, g
             FROM (SELECT ST_SetSRID(ST_MakeLine(ST_MakePoint(?, ?), ST_MakePoint(?, ?)), 4326) AS g) t',
            [$sumber, 'Jalan Uji', $sumber === 'survei' ? 'gang_survei' : 'residential', $source, $target, $terpecah,
                $a->lng, $a->lat, $b->lng, $b->lat],
        );
    }

    private function jaringanUji(bool $denganGang = false): void
    {
        $this->ruas(1, [-500, -20], 5, [0, -20]);
        $this->ruas(5, [0, -20], 2, [500, -20]);
        $this->ruas(2, [500, -20], 3, [500, 300]);
        $this->ruas(3, [500, 300], 6, [0, 300]);
        $this->ruas(6, [0, 300], 4, [-500, 300]);
        if ($denganGang) {
            $this->ruas(5, [0, -20], 6, [0, 300], 'survei');
        }

        $import = app(ImportJaringanJalan::class);
        $import->tandaiKomponenUtama();
        $import->gantiVersi();
    }

    /** @return array<int, array> fitur jangkauan per menit */
    private function jangkauan(string $jaringan = 'lengkap'): array
    {
        $data = $this->getJson('/api/peta/jangkauan'.($jaringan === 'osm' ? '?jaringan=osm' : ''))
            ->assertOk()
            ->assertJsonPath('type', 'FeatureCollection')
            ->json();

        return collect($data['features'])->keyBy('properties.menit')->all();
    }

    private function terjangkau(array $fitur, float $x, float $y): bool
    {
        $p = $this->titik($x, $y);

        return (bool) DB::scalar(
            'SELECT ST_Contains(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326), ST_SetSRID(ST_MakePoint(?, ?), 4326))',
            [json_encode($fitur['geometry']), $p->lng, $p->lat],
        );
    }

    public function test_jangkauan_mengikuti_jalan_bukan_garis_lurus(): void
    {
        $this->jaringanUji();
        $pita = $this->jangkauan();

        $this->assertSame([20, 15, 10, 5], array_keys($pita), 'Pita terurut dari terjauh supaya digambar paling bawah');
        $this->assertSame(400, $pita[5]['properties']['jarak_m']);
        $this->assertContains($pita[5]['geometry']['type'], ['Polygon', 'MultiPolygon']);

        // Sepanjang jalan bawah: 300 m dari titik tempel masuk 5 menit (batas 400 - 20 = 380 m), 450 m tidak.
        $this->assertTrue($this->terjangkau($pita[5], 300, -20));
        $this->assertFalse($this->terjangkau($pita[5], 450, -20));
        $this->assertTrue($this->terjangkau($pita[10], 450, -20));

        // Seberang sungai: garis lurus cuma 300 m, tapi lewat jembatan 20 + 500 + 320 + 500 = 1.340 m.
        $this->assertFalse($this->terjangkau($pita[10], 0, 300), 'Masuk buffer 800 m, tapi tidak terjangkau 10 menit');
        $this->assertFalse($this->terjangkau($pita[15], 0, 300));
        $this->assertTrue($this->terjangkau($pita[20], 0, 300));

        // Ruas dipotong di tengah: jembatan terjangkau sampai 780 - 500 = 280 m pada 10 menit.
        $this->assertTrue($this->terjangkau($pita[10], 500, 150));
        $this->assertFalse($this->terjangkau($pita[10], 500, 300));

        // Area di luar jalan tidak ikut terjangkau (bukan lingkaran).
        $this->assertFalse($this->terjangkau($pita[20], 0, -300));
    }

    public function test_gang_survei_memperluas_jangkauan_dan_bisa_dibandingkan_dengan_osm(): void
    {
        $this->jaringanUji(denganGang: true);

        $lengkap = $this->jangkauan();
        $osm = $this->jangkauan('osm');

        // Lewat gang, seberang sungai hanya ±320 m dari kampus.
        $this->assertTrue($this->terjangkau($lengkap[5], 0, 300));
        $this->assertFalse($this->terjangkau($osm[15], 0, 300), 'Tanpa gang tetap harus memutar lewat jembatan');
        $this->assertGreaterThan($osm[10]['properties']['luas_ha'], $lengkap[10]['properties']['luas_ha']);
    }

    public function test_jangkauan_dihitung_ulang_setelah_jaringan_berubah(): void
    {
        $this->jaringanUji();
        $this->assertFalse($this->terjangkau($this->jangkauan()[5], 0, 300));

        // Gang baru diunggah: JaringanSurvei mengganti versi cache, jadi hasil lama tidak dipakai lagi.
        $this->ruas(5, [0, -20], 6, [0, 300], 'survei');
        $import = app(ImportJaringanJalan::class);
        $import->tandaiKomponenUtama();
        $import->gantiVersi();

        $this->assertTrue($this->terjangkau($this->jangkauan()[5], 0, 300));
    }

    public function test_jangkauan_tanpa_jaringan_jalan(): void
    {
        $this->getJson('/api/peta/jangkauan')
            ->assertOk()
            ->assertJsonPath('tersedia', false)
            ->assertJsonPath('features', []);
    }

    public function test_layer_jaringan_jalan_dibagi_osm_dan_gang_survei(): void
    {
        $this->jaringanUji(denganGang: true);
        // Ruas terpecah diwakili potongannya, dan ruas di luar radius tidak ikut.
        $this->ruas(7, [-500, -20], 8, [500, -20], 'osm', terpecah: true);
        $this->ruas(9, [5000, 0], 10, [5200, 0]);

        $data = $this->getJson('/api/peta/jaringan-jalan')->assertOk()->json();
        $fitur = collect($data['features'])->keyBy('properties.kategori');

        $this->assertSame(['osm', 'survei'], $fitur->keys()->all());
        $this->assertSame(5, $fitur['osm']['properties']['jumlah_ruas']);
        $this->assertEqualsWithDelta(2.32, $fitur['osm']['properties']['panjang_km'], 0.02);
        $this->assertSame(1, $fitur['survei']['properties']['jumlah_ruas']);
        $this->assertSame('Gang hasil survei', $fitur['survei']['properties']['label']);
        $this->assertSame('MultiLineString', $fitur['survei']['geometry']['type']);
    }

    public function test_halaman_jelajah_memuat_kontrol_layer(): void
    {
        $this->get('/jelajah')
            ->assertOk()
            ->assertSee(json_encode(route('api.peta.jangkauan')), false)
            ->assertSee(json_encode(route('api.peta.jaringanJalan')), false)
            ->assertSee('id="panel-layer"', false)
            ->assertSee('data-layer="jangkauan"', false)
            ->assertSee('data-layer="area"', false)
            ->assertSee('id="f-jarak-ket"', false);
    }
}
