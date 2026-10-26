<?php

namespace Tests\Feature\Pengguna;

use App\Enums\StatusKos;
use App\Enums\TipeKos;
use App\Models\Fasilitas;
use App\Models\FotoKos;
use App\Models\Kos;
use App\Support\Koordinat;
use Database\Seeders\FasilitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HalamanPenggunaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FasilitasSeeder::class);
    }

    private function kosAktif(string $nama, int $dilihat, int $harga, array $fasilitas = ['WiFi']): Kos
    {
        $kampus = Koordinat::kampus();
        $kos = Kos::create([
            'nama' => $nama, 'alamat' => 'Jl. Uji', 'tipe' => TipeKos::Putri, 'harga_per_bulan' => $harga,
            'periode_minimal_bulan' => 1, 'status' => StatusKos::Aktif,
            'lokasi' => new Koordinat(lat: $kampus->lat + 0.004, lng: $kampus->lng),
        ]);
        // jumlah_dilihat sengaja tidak fillable (hanya naik lewat API), jadi diisi langsung.
        $kos->forceFill(['jumlah_dilihat' => $dilihat])->save();
        $kos->fasilitas()->attach(Fasilitas::whereIn('nama', $fasilitas)->pluck('id'));
        FotoKos::create(['kos_id' => $kos->id, 'path' => "kos/{$kos->id}/a.jpg", 'is_utama' => true]);

        return $kos;
    }

    public function test_beranda_menampilkan_kos_aktif_terpopuler(): void
    {
        $this->kosAktif('Kos Sepi', 1, 700_000);
        $this->kosAktif('Kos Ramai', 50, 900_000);
        $this->kosAktif('Kos Lumayan', 10, 800_000);
        $this->kosAktif('Kos Paling Sepi', 0, 600_000);
        Kos::create(['nama' => 'Kos Draft Rahasia']);

        $response = $this->get('/pengguna/beranda')->assertOk();

        $this->assertSame(['Kos Ramai', 'Kos Lumayan', 'Kos Sepi'], $response->viewData('kosKartu')->pluck('nama')->all());
        $this->assertCount(4, $response->viewData('kosList'));
        $response->assertSee('4 Kos Terdaftar')
            ->assertSee('Rp900.000')
            ->assertSee('jelajah-kos?kos=', false)
            ->assertDontSee('Kos Draft Rahasia')
            ->assertDontSee('Kos Putri Melati');
    }

    public function test_beranda_tanpa_kos_aktif_tidak_error(): void
    {
        $this->get('/pengguna/beranda')->assertOk()->assertSee('Belum ada kos yang ditampilkan');
    }

    public function test_jelajah_menampilkan_filter_dari_data_asli(): void
    {
        $this->kosAktif('Kos A', 0, 1_250_000, ['WiFi', 'AC']);
        $draft = Kos::create(['nama' => 'Kos Draft']);
        $draft->fasilitas()->attach(Fasilitas::where('nama', 'Mushola')->value('id'));

        $response = $this->get('/pengguna/jelajah-kos?q=sancang&kos=5')->assertOk();

        $this->assertSame(['AC', 'WiFi'], $response->viewData('fasilitasFilter')->pluck('nama')->all(), 'Fasilitas yang hanya dimiliki kos draft tidak ditampilkan');
        $this->assertSame(1_300_000, $response->viewData('hargaMax'));
        $this->assertSame(['q' => 'sancang', 'kos' => 5], $response->viewData('awal'));
        $response->assertSee('value="sancang"', false)
            ->assertSee('data-value="rekomendasi"', false)
            ->assertDontSee('Kos Putri Melati');
    }

    public function test_jelajah_kosong_tetap_terbuka(): void
    {
        $this->get('/pengguna/jelajah-kos')->assertOk()->assertSee('Belum ada data fasilitas');
    }
}