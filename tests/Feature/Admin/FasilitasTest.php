<?php

namespace Tests\Feature\Admin;

use App\Models\Fasilitas;
use App\Models\Kos;
use App\Models\User;
use Database\Seeders\FasilitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FasilitasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FasilitasSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    public function test_daftar_fasilitas_dari_database_dengan_jumlah_kos(): void
    {
        $wifi = Fasilitas::where('nama', 'WiFi')->first();
        Kos::factory()->count(3)->create()->each(fn ($k) => $k->fasilitas()->attach($wifi->id));

        $this->get('/admin/fasilitas')
            ->assertOk()
            ->assertSee('24 fasilitas terdaftar')
            ->assertSee('Digunakan pada 3 data kos')
            ->assertSee('Water Heater')
            ->assertDontSee('Keamanan 24 Jam');
    }

    public function test_tambah_fasilitas_dengan_ikon(): void
    {
        $this->post('/admin/fasilitas', ['nama_fasilitas' => '  Rooftop   Santai ', 'ikon' => ''])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('fasilitas', ['nama' => 'Rooftop Santai', 'ikon' => null]);

        $this->post('/admin/fasilitas', ['nama_fasilitas' => 'Parkir Sepeda', 'ikon' => 'parkir']);
        $this->assertDatabaseHas('fasilitas', ['nama' => 'Parkir Sepeda', 'ikon' => 'parkir']);
    }

    public function test_nama_fasilitas_tidak_boleh_kembar_tanpa_peduli_huruf_besar(): void
    {
        $this->post('/admin/fasilitas', ['nama_fasilitas' => 'wifi'])
            ->assertSessionHasErrors(['nama_fasilitas' => 'Fasilitas "wifi" sudah ada.']);

        $this->assertSame(1, Fasilitas::whereRaw('LOWER(nama) = ?', ['wifi'])->count());
    }

    public function test_validasi_nama_kosong_dan_ikon_asing(): void
    {
        $this->post('/admin/fasilitas', ['nama_fasilitas' => '', 'ikon' => 'roket'])
            ->assertSessionHasErrors(['nama_fasilitas', 'ikon']);
    }

    public function test_ubah_fasilitas(): void
    {
        $ac = Fasilitas::where('nama', 'AC')->first();

        $this->put("/admin/fasilitas/{$ac->id}", ['nama_fasilitas' => 'AC (Pendingin)', 'ikon' => 'ac'])
            ->assertSessionHasNoErrors();

        $this->assertSame('AC (Pendingin)', $ac->fresh()->nama);
    }

    public function test_ubah_dengan_nama_sendiri_tetap_boleh(): void
    {
        $ac = Fasilitas::where('nama', 'AC')->first();

        $this->put("/admin/fasilitas/{$ac->id}", ['nama_fasilitas' => 'AC', 'ikon' => 'ac'])->assertSessionHasNoErrors();
    }

    public function test_ubah_ke_nama_fasilitas_lain_ditolak(): void
    {
        $ac = Fasilitas::where('nama', 'AC')->first();

        $this->put("/admin/fasilitas/{$ac->id}", ['nama_fasilitas' => 'WiFi'])->assertSessionHasErrors('nama_fasilitas');
        $this->assertSame('AC', $ac->fresh()->nama);
    }

    public function test_hapus_fasilitas_melepas_dari_kos(): void
    {
        $kulkas = Fasilitas::where('nama', 'Kulkas')->first();
        $kos = Kos::factory()->create();
        $kos->fasilitas()->attach($kulkas->id);

        $this->delete("/admin/fasilitas/{$kulkas->id}")
            ->assertSessionHas('success', 'Fasilitas Kulkas dihapus dan dilepas dari 1 kos.');

        $this->assertNull(Fasilitas::find($kulkas->id));
        $this->assertNotNull(Kos::find($kos->id), 'Kos tidak ikut terhapus');
        $this->assertSame(0, DB::table('fasilitas_kos')->where('fasilitas_id', $kulkas->id)->count());
    }

    public function test_halaman_fasilitas_butuh_login(): void
    {
        auth()->logout();
        $this->get('/admin/fasilitas')->assertRedirect('/admin/login');
        $this->post('/admin/fasilitas', ['nama_fasilitas' => 'X'])->assertRedirect('/admin/login');
    }
}
