<?php

namespace Tests\Feature;

use App\Enums\StatusKos;
use App\Enums\TipeKos;
use App\Models\Kos;
use Database\Seeders\FasilitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportKosTest extends TestCase
{
    use RefreshDatabase;

    private string $csv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FasilitasSeeder::class);
        $this->csv = base_path('tests/Fixtures/pendataan-kos-contoh.csv');
    }

    public function test_import_membuat_kos_draft_yang_sudah_dibakukan(): void
    {
        $this->artisan('kos:import', ['file' => $this->csv])
            ->expectsOutputToContain('Kos baru (Draft): 5')
            ->assertSuccessful();

        $this->assertSame(5, Kos::count());
        $this->assertSame(5, Kos::where('status', StatusKos::Draft->value)->count());

        $satu = Kos::where('nama', 'Kos Contoh Satu')->with('fasilitas')->firstOrFail();
        $this->assertSame('081200001111', $satu->no_telepon);
        $this->assertSame(TipeKos::Putri, $satu->tipe);
        $this->assertSame(1, $satu->periode_minimal_bulan);
        $this->assertSame(900000, $satu->harga_per_bulan);
        $this->assertSame('Jl. Contoh No. 1, Babakan, Bogor Tengah', $satu->alamat);
        $this->assertSame(['Air Termasuk', 'Kamar Mandi Dalam', 'Kasur', 'Listrik Termasuk', 'WiFi'], $satu->fasilitas->pluck('nama')->all());
        $this->assertStringContainsString('Link foto di spreadsheet', $satu->catatan_import);
    }

    public function test_baris_bernama_sama_digabung(): void
    {
        $this->artisan('kos:import', ['file' => $this->csv])->assertSuccessful();

        $ganda = Kos::where('nama', 'Kos Contoh Ganda')->with('fasilitas')->get();
        $this->assertCount(1, $ganda);
        $this->assertSame(1042000, $ganda[0]->harga_per_bulan, '12.500.000 / 12, dibulatkan ke ribuan');
        $this->assertSame('081200002222', $ganda[0]->no_telepon);
        $this->assertSame(['AC', 'CCTV', 'Dapur', 'Kamar Mandi Dalam', 'Kasur'], $ganda[0]->fasilitas->pluck('nama')->all());
        $this->assertStringContainsString('Digabung dari 2 baris', $ganda[0]->catatan_import);
    }

    public function test_plus_code_menjadi_titik_lokasi(): void
    {
        $this->artisan('kos:import', ['file' => $this->csv])->assertSuccessful();

        $kos = Kos::where('nama', 'Kos Contoh Plus Code')->firstOrFail();
        $this->assertEqualsWithDelta(-6.579187, $kos->lokasi->lat, 2e-6);
        $this->assertEqualsWithDelta(106.812172, $kos->lokasi->lng, 2e-6);
        $this->assertSame(TipeKos::Putra, $kos->tipe);
        $this->assertSame(800000, $kos->harga_per_bulan, 'Nilai 800000.2 dibulatkan');
    }

    public function test_baris_hanya_nama_tetap_masuk_sebagai_draft_kosong(): void
    {
        $this->artisan('kos:import', ['file' => $this->csv])->assertSuccessful();

        $kosong = Kos::where('nama', 'Kos Contoh Kosong')->firstOrFail();
        $this->assertNull($kosong->harga_per_bulan);
        $this->assertNotSame([], $kosong->kekuranganUntukAktif());
    }

    public function test_dijalankan_ulang_tidak_menggandakan_dan_tidak_menimpa(): void
    {
        $this->artisan('kos:import', ['file' => $this->csv])->assertSuccessful();
        Kos::where('nama', 'Kos Contoh Satu')->update(['harga_per_bulan' => 950000]);

        $this->artisan('kos:import', ['file' => $this->csv])
            ->expectsOutputToContain('Dilewati: 5')
            ->assertSuccessful();

        $this->assertSame(5, Kos::count());
        $this->assertSame(950000, Kos::where('nama', 'Kos Contoh Satu')->value('harga_per_bulan'));
    }

    public function test_dry_run_tidak_menyimpan_apa_pun(): void
    {
        $this->artisan('kos:import', ['file' => $this->csv, '--dry-run' => true])
            ->expectsOutputToContain('DRY RUN')
            ->assertSuccessful();

        $this->assertSame(0, Kos::count());
    }

    public function test_csv_dengan_pemisah_titik_koma_dari_excel(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'kos').'.csv';
        file_put_contents($path, "\xEF\xBB\xBFNama Kos;Jenis Kos;Harga Sewa Per Bulan\nKos Titik Koma;Wanita;750000\n");

        $this->artisan('kos:import', ['file' => $path])->assertSuccessful();

        $this->assertSame(750000, Kos::where('nama', 'Kos Titik Koma')->value('harga_per_bulan'));
        @unlink($path);
    }

    public function test_file_tidak_ada_atau_tanpa_kolom_nama_gagal_dengan_pesan_jelas(): void
    {
        $this->artisan('kos:import', ['file' => '/tidak/ada.csv'])
            ->expectsOutputToContain('File tidak ditemukan')
            ->assertFailed();

        $path = tempnam(sys_get_temp_dir(), 'kos').'.csv';
        file_put_contents($path, "Judul,Lain\na,b\n");
        $this->artisan('kos:import', ['file' => $path])
            ->expectsOutputToContain('Kolom "Nama Kos" tidak ditemukan')
            ->assertFailed();
        @unlink($path);
    }

    public function test_catatan_import_tampil_di_halaman_detail_admin(): void
    {
        $this->artisan('kos:import', ['file' => $this->csv])->assertSuccessful();
        $kos = Kos::where('nama', 'Kos Contoh Pilihan')->firstOrFail();

        $this->actingAs(\App\Models\User::factory()->create())
            ->get("/admin/datakos/{$kos->id}")
            ->assertOk()
            ->assertSee('Catatan import spreadsheet')
            ->assertSee('Fasilitas lain di spreadsheet: Taman, Rooftop.');
    }
}
