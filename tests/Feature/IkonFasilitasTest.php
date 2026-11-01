<?php

namespace Tests\Feature;

use App\Models\Fasilitas;
use Database\Seeders\FasilitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class IkonFasilitasTest extends TestCase
{
    use RefreshDatabase;

    private function render(?string $ikon): string
    {
        return trim(Blade::render('<x-ikon-fasilitas :ikon="$ikon" />', ['ikon' => $ikon]));
    }

    public function test_setiap_kunci_ikon_punya_gambar_sendiri(): void
    {
        $centang = $this->render(null);
        $gambar = [];

        foreach (array_keys(Fasilitas::IKON) as $kunci) {
            $svg = $this->render($kunci);
            $this->assertStringStartsWith('<svg', $svg, "Ikon {$kunci}");
            $this->assertNotSame($centang, $svg, "Ikon {$kunci} masih memakai centang default");
            $gambar[$kunci] = $svg;
        }

        $this->assertCount(count($gambar), array_unique($gambar), 'Dua kunci ikon memakai gambar yang sama');
        $this->assertSame($centang, $this->render('tidak-ada'), 'Kunci tak dikenal memakai centang');
    }

    public function test_semua_master_fasilitas_memakai_ikon_yang_terdaftar(): void
    {
        foreach (FasilitasSeeder::DAFTAR as $nama => $ikon) {
            $this->assertNotNull($ikon, "{$nama} belum punya ikon");
            $this->assertArrayHasKey($ikon, Fasilitas::IKON, "Ikon {$nama} tidak terdaftar");
        }
    }

    public function test_migrasi_mengisi_ikon_kosong_tanpa_menimpa_pilihan_admin(): void
    {
        Fasilitas::query()->delete();
        Fasilitas::insert([
            ['nama' => 'Kasur', 'ikon' => null, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'kulkas', 'ikon' => null, 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Parkir Motor', 'ikon' => 'parkir', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Meja', 'ikon' => 'tv', 'created_at' => now(), 'updated_at' => now()],
            ['nama' => 'Rooftop', 'ikon' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $migrasi = require database_path('migrations/2026_11_01_000001_isi_ikon_master_fasilitas.php');
        $migrasi->up();

        $ikon = Fasilitas::pluck('ikon', 'nama');
        $this->assertSame('kasur', $ikon['Kasur']);
        $this->assertSame('kulkas', $ikon['kulkas'], 'Nama dicocokkan tanpa peduli huruf besar');
        $this->assertSame('motor', $ikon['Parkir Motor'], 'Ikon bawaan lama diganti yang lebih spesifik');
        $this->assertSame('tv', $ikon['Meja'], 'Pilihan admin tidak ditimpa');
        $this->assertNull($ikon['Rooftop'], 'Fasilitas di luar master tidak diubah');
    }

    public function test_admin_bisa_memilih_ikon_baru(): void
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $this->post('/admin/fasilitas', ['nama_fasilitas' => 'Rooftop', 'ikon' => 'jemur'])->assertSessionHasNoErrors();

        $this->assertSame('jemur', Fasilitas::where('nama', 'Rooftop')->value('ikon'));
    }
}