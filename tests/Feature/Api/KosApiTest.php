<?php

namespace Tests\Feature\Api;

use App\Enums\StatusKos;
use App\Enums\TipeKos;
use App\Models\Fasilitas;
use App\Models\FotoKos;
use App\Models\Kos;
use App\Support\Koordinat;
use Database\Seeders\FasilitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KosApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FasilitasSeeder::class);
    }

    /** Buat kos aktif sejauh $meter ke utara dari kampus (0,001 derajat lintang ~ 110,6 m di sini). */
    private function kos(string $nama, int $meter, int $harga, TipeKos $tipe, array $fasilitas = [], StatusKos $status = StatusKos::Aktif): Kos
    {
        $kampus = Koordinat::kampus();
        $kos = Kos::create([
            'nama' => $nama,
            'alamat' => "Jl. Uji {$nama}",
            'tipe' => $tipe,
            'harga_per_bulan' => $harga,
            'periode_minimal_bulan' => 1,
            'lokasi' => new Koordinat(lat: $kampus->lat + $meter / 110_600, lng: $kampus->lng),
            'status' => $status,
        ]);
        $kos->fasilitas()->attach(Fasilitas::whereIn('nama', $fasilitas)->pluck('id'));
        FotoKos::create(['kos_id' => $kos->id, 'path' => "kos/{$kos->id}/a.jpg", 'is_utama' => true]);

        return $kos;
    }

    private function dataUji(): void
    {
        $this->kos('Kos Dekat Mahal', 300, 1_500_000, TipeKos::Putri, ['WiFi', 'AC', 'Kamar Mandi Dalam']);
        $this->kos('Kos Sedang Murah', 1_500, 700_000, TipeKos::Putra, ['WiFi']);
        $this->kos('Kos Jauh Lengkap', 4_000, 1_100_000, TipeKos::Putri, ['WiFi', 'AC', 'Kamar Mandi Dalam', 'Dapur', 'Laundry']);
        $this->kos('Kos Draft', 100, 500_000, TipeKos::Putri, ['WiFi'], StatusKos::Draft);
    }

    private function nama($response): array
    {
        return array_column($response->json('data'), 'nama');
    }

    public function test_hanya_kos_aktif_yang_tampil_dengan_bentuk_data_lengkap(): void
    {
        $this->dataUji();

        $response = $this->getJson('/api/kos')->assertOk()->assertJsonPath('meta.jumlah', 3);

        $this->assertNotContains('Kos Draft', $this->nama($response));
        $response->assertJsonStructure(['data' => [['id', 'nama', 'tipe', 'harga', 'periode', 'telepon', 'alamat', 'fasilitas' => [['id', 'nama', 'ikon']], 'fotos', 'lat', 'lng', 'jarak_m', 'skor']]]);

        $dekat = collect($response->json('data'))->firstWhere('nama', 'Kos Dekat Mahal');
        $this->assertEqualsWithDelta(300, $dekat['jarak_m'], 3);
        $this->assertSame('Putri', $dekat['tipe']);
        $this->assertStringEndsWith("/storage/kos/{$dekat['id']}/a.jpg", $dekat['fotos'][0]);
    }

    public function test_filter_radius_memakai_buffer_postgis(): void
    {
        $this->dataUji();

        $this->assertSame(['Kos Dekat Mahal'], $this->nama($this->getJson('/api/kos?jarak_max_km=1&urut=jarak')));
        $this->assertSame(['Kos Dekat Mahal', 'Kos Sedang Murah'], $this->nama($this->getJson('/api/kos?jarak_max_km=2&urut=jarak')));
    }

    public function test_filter_harga_tipe_dan_semua_fasilitas_harus_ada(): void
    {
        $this->dataUji();
        $ac = Fasilitas::where('nama', 'AC')->value('id');
        $dapur = Fasilitas::where('nama', 'Dapur')->value('id');

        $this->assertSame(['Kos Sedang Murah'], $this->nama($this->getJson('/api/kos?harga_max=800000')));
        $this->assertSame(['Kos Sedang Murah'], $this->nama($this->getJson('/api/kos?tipe=putra')));
        $this->assertCount(3, $this->getJson('/api/kos?tipe=semua')->json('data'));
        $this->assertSame(['Kos Jauh Lengkap'], $this->nama($this->getJson("/api/kos?fasilitas[]={$ac}&fasilitas[]={$dapur}")));
    }

    public function test_pencarian_nama_dan_alamat_tanpa_peduli_huruf_besar(): void
    {
        $this->dataUji();

        $this->assertSame(['Kos Jauh Lengkap'], $this->nama($this->getJson('/api/kos?q=jauh')));
        $this->assertSame(['Kos Sedang Murah'], $this->nama($this->getJson('/api/kos?q='.urlencode('JL. UJI KOS SEDANG'))));
        $this->assertSame([], $this->nama($this->getJson('/api/kos?q=100%25')), 'Tanda % dianggap teks biasa');
    }

    public function test_urutan_jarak_murah_mahal(): void
    {
        $this->dataUji();

        $this->assertSame(['Kos Dekat Mahal', 'Kos Sedang Murah', 'Kos Jauh Lengkap'], $this->nama($this->getJson('/api/kos?urut=jarak')));
        $this->assertSame(['Kos Sedang Murah', 'Kos Jauh Lengkap', 'Kos Dekat Mahal'], $this->nama($this->getJson('/api/kos?urut=murah')));
        $this->assertSame(['Kos Dekat Mahal', 'Kos Jauh Lengkap', 'Kos Sedang Murah'], $this->nama($this->getJson('/api/kos?urut=mahal')));
    }

    public function test_urutan_rekomendasi_memakai_skor_saw(): void
    {
        $this->dataUji();

        $response = $this->getJson('/api/kos')->assertJsonPath('meta.urut', 'rekomendasi');
        $skor = collect($response->json('data'))->pluck('skor', 'nama');

        // Jarak 300/1500/4000 m, harga 1,5jt/700rb/1,1jt, fasilitas 3/1/5.
        // Dekat: 0,5*1 + 0,3*0 + 0,2*0,5 = 0,6
        // Sedang: 0,5*(2500/3700) + 0,3*1 + 0,2*0 = 0,6378
        // Jauh: 0,5*0 + 0,3*0,5 + 0,2*1 = 0,35
        $this->assertEqualsWithDelta(0.6, $skor['Kos Dekat Mahal'], 0.002);
        $this->assertEqualsWithDelta(0.6378, $skor['Kos Sedang Murah'], 0.002);
        $this->assertEqualsWithDelta(0.35, $skor['Kos Jauh Lengkap'], 0.002);
        $this->assertSame(['Kos Sedang Murah', 'Kos Dekat Mahal', 'Kos Jauh Lengkap'], $this->nama($response));
    }

    public function test_skor_relatif_terhadap_hasil_filter(): void
    {
        $this->dataUji();

        $data = $this->getJson('/api/kos?jarak_max_km=1')->json('data');
        $this->assertSame(1.0, (float) $data[0]['skor'], 'Satu-satunya kos dalam 1 km dapat skor penuh');
    }

    public function test_hasil_kosong_tetap_valid(): void
    {
        $this->getJson('/api/kos')->assertOk()->assertJson(['data' => [], 'meta' => ['jumlah' => 0]]);
    }

    public function test_parameter_tidak_valid_ditolak_dengan_422(): void
    {
        $this->getJson('/api/kos?jarak_max_km=50&urut=acak&tipe=asrama&harga_max=murah')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['jarak_max_km', 'urut', 'tipe', 'harga_max']);
    }

    public function test_hitung_dilihat_sekali_per_ip_per_kos(): void
    {
        $this->dataUji();
        $kos = Kos::where('nama', 'Kos Dekat Mahal')->first();

        $this->postJson("/api/kos/{$kos->id}/dilihat")->assertOk()->assertJson(['jumlah_dilihat' => 1, 'dihitung' => true]);
        $this->postJson("/api/kos/{$kos->id}/dilihat")->assertOk()->assertJson(['jumlah_dilihat' => 1, 'dihitung' => false]);
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])
            ->postJson("/api/kos/{$kos->id}/dilihat")->assertJson(['jumlah_dilihat' => 2]);
    }

    public function test_dilihat_untuk_kos_draft_atau_tidak_ada_404(): void
    {
        $this->dataUji();
        $draft = Kos::where('nama', 'Kos Draft')->first();

        $this->postJson("/api/kos/{$draft->id}/dilihat")->assertNotFound();
        $this->postJson('/api/kos/99999/dilihat')->assertNotFound();
        $this->assertSame(0, $draft->fresh()->jumlah_dilihat);
    }
}