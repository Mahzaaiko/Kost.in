<?php

namespace Tests\Feature\Admin;

use App\Enums\StatusKos;
use App\Enums\TipeKos;
use App\Models\Fasilitas;
use App\Models\FotoKos;
use App\Models\Kos;
use App\Models\User;
use App\Support\Koordinat;
use Database\Seeders\FasilitasSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KosCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(FasilitasSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    /** Payload form lengkap yang valid, titiknya sekitar 300 m dari kampus. */
    private function formLengkap(array $timpa = []): array
    {
        $kampus = Koordinat::kampus();

        return array_merge([
            'nama_kos' => 'Kos Uji Lengkap',
            'no_telepon' => '0812 3456 7890',
            'alamat' => 'Jl. Sancang Dalam No. 26, Babakan, Bogor Tengah',
            'tipe_kos' => 'Putri',
            'periode_sewa' => '3 Bulan',
            'harga' => '850000',
            'deskripsi' => 'Dekat kampus.',
            'fasilitas' => Fasilitas::whereIn('nama', ['WiFi', 'AC'])->pluck('id')->map(fn ($id) => (string) $id)->all(),
            'latitude' => (string) ($kampus->lat + 0.0027),
            'longitude' => (string) $kampus->lng,
            'foto' => [
                UploadedFile::fake()->image('depan.jpg', 800, 600),
                UploadedFile::fake()->image('kamar.png', 800, 600),
            ],
            'foto_utama' => 'baru:1',
        ], $timpa);
    }

    private function kosLengkap(array $atribut = []): Kos
    {
        $kos = Kos::factory()->create($atribut);
        FotoKos::create(['kos_id' => $kos->id, 'path' => "kos/{$kos->id}/a.jpg", 'is_utama' => true, 'urutan' => 1]);

        return $kos;
    }

    // ---------- Daftar ----------

    public function test_daftar_kos_menampilkan_data_asli_dengan_jarak_dan_status(): void
    {
        $kampus = Koordinat::kampus();
        Kos::factory()->create([
            'nama' => 'Kos Dekat',
            'tipe' => TipeKos::Putra,
            'harga_per_bulan' => 700000,
            'lokasi' => new Koordinat(lat: $kampus->lat + 0.0027, lng: $kampus->lng),
        ]);
        Kos::create(['nama' => 'Kos Tanpa Titik']);

        $this->get('/admin/datakos')
            ->assertOk()
            ->assertSee('Kos Dekat')
            ->assertSee('Rp700.000')
            ->assertSee('300 m')
            ->assertSee('Kos Tanpa Titik')
            ->assertSee('2 data kos')
            ->assertDontSee('Kos Putri Melati');
    }

    // ---------- Tambah ----------

    public function test_form_tambah_menampilkan_fasilitas_dari_database(): void
    {
        $this->get('/admin/datakos/tambah')
            ->assertOk()
            ->assertSee('Kamar Mandi Luar')
            ->assertSee('Listrik Termasuk')
            ->assertSee('name="fasilitas[]"', false);
    }

    public function test_simpan_kos_dengan_nama_saja_menjadi_draft(): void
    {
        $this->post('/admin/datakos', ['nama_kos' => 'Kos Minimal'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $kos = Kos::where('nama', 'Kos Minimal')->firstOrFail();
        $this->assertSame(StatusKos::Draft, $kos->status);
        $this->assertNull($kos->lokasi);
    }

    public function test_simpan_kos_lengkap_dengan_foto_fasilitas_dan_lokasi(): void
    {
        $response = $this->post('/admin/datakos', $this->formLengkap());

        $kos = Kos::where('nama', 'Kos Uji Lengkap')->firstOrFail();
        $response->assertRedirect(route('admin.datakos.detail', $kos));

        $this->assertSame(TipeKos::Putri, $kos->tipe);
        $this->assertSame(3, $kos->periode_minimal_bulan);
        $this->assertSame(850000, $kos->harga_per_bulan);
        $this->assertSame(StatusKos::Draft, $kos->status, 'Kos baru selalu mulai sebagai Draft');
        $this->assertEqualsWithDelta(Koordinat::kampus()->lat + 0.0027, $kos->lokasi->lat, 1e-6);
        $this->assertSame(['AC', 'WiFi'], $kos->fasilitas->pluck('nama')->all());

        // Urutan upload (relasi foto() sendiri menaruh foto utama paling depan).
        $foto = FotoKos::where('kos_id', $kos->id)->orderBy('urutan')->get();
        $this->assertCount(2, $foto);
        $this->assertFalse($foto[0]->is_utama);
        $this->assertTrue($foto[1]->is_utama, 'Foto ke-2 dipilih sebagai utama');
        foreach ($foto as $f) {
            Storage::disk('public')->assertExists($f->path);
            $this->assertStringStartsWith("kos/{$kos->id}/", $f->path);
        }
        $this->assertSame([], $kos->kekuranganUntukAktif());
    }

    public function test_foto_pertama_jadi_utama_kalau_tidak_dipilih(): void
    {
        $this->post('/admin/datakos', $this->formLengkap(['foto_utama' => '']));

        $kos = Kos::where('nama', 'Kos Uji Lengkap')->firstOrFail();
        $this->assertSame(1, $kos->foto()->where('is_utama', true)->count());
        $this->assertSame(FotoKos::where('kos_id', $kos->id)->orderBy('urutan')->value('id'), $kos->fotoUtama->id);
    }

    public function test_harga_dengan_titik_ribuan_diterima(): void
    {
        $this->post('/admin/datakos', $this->formLengkap(['harga' => '1.250.000']))->assertSessionHasNoErrors();

        $this->assertSame(1250000, Kos::firstOrFail()->harga_per_bulan);
    }

    public function test_validasi_menolak_input_tidak_valid(): void
    {
        $this->post('/admin/datakos', [
            'nama_kos' => '',
            'tipe_kos' => 'Asrama',
            'periode_sewa' => '2 Bulan',
            'harga' => '50000',
            'no_telepon' => 'telp-saya',
            'latitude' => '-6.59',
        ])->assertSessionHasErrors(['nama_kos', 'tipe_kos', 'periode_sewa', 'harga', 'no_telepon', 'longitude']);

        $this->assertSame(0, Kos::count());
    }

    public function test_lokasi_lebih_dari_15_km_dari_kampus_ditolak(): void
    {
        // Monas, Jakarta: sekitar 45 km dari kampus.
        $this->post('/admin/datakos', $this->formLengkap(['latitude' => '-6.1754', 'longitude' => '106.8272']))
            ->assertSessionHasErrors('latitude');

        $this->assertSame(0, Kos::count());
    }

    public function test_lat_lng_tertukar_ditolak(): void
    {
        $this->post('/admin/datakos', $this->formLengkap(['latitude' => '106.8067', 'longitude' => '-6.5896']))
            ->assertSessionHasErrors('latitude');
    }

    public function test_foto_lebih_dari_3mb_atau_bukan_gambar_ditolak(): void
    {
        $this->post('/admin/datakos', $this->formLengkap([
            'foto' => [UploadedFile::fake()->image('besar.jpg')->size(3073)],
        ]))->assertSessionHasErrors('foto.0');

        $this->post('/admin/datakos', $this->formLengkap([
            'foto' => [UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf')],
        ]))->assertSessionHasErrors('foto.0');

        $this->assertSame(0, Kos::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_foto_lebih_dari_10_ditolak(): void
    {
        $foto = array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg"), range(1, 11));

        $this->post('/admin/datakos', $this->formLengkap(['foto' => $foto]))
            ->assertSessionHasErrors('foto');
    }

    // ---------- Detail ----------

    public function test_detail_menampilkan_data_dan_kekurangan(): void
    {
        $kos = Kos::create(['nama' => 'Kos Draft Setengah', 'harga_per_bulan' => 900000]);

        $this->get("/admin/datakos/{$kos->id}")
            ->assertOk()
            ->assertSee('Kos Draft Setengah')
            ->assertSee('Rp900.000')
            ->assertSee('Belum bisa diaktifkan')
            ->assertSee('titik lokasi');
    }

    public function test_detail_kos_yang_tidak_ada_404(): void
    {
        $this->get('/admin/datakos/99999')->assertNotFound();
    }

    // ---------- Edit ----------

    public function test_form_edit_terisi_data_lama(): void
    {
        $kos = $this->kosLengkap(['nama' => 'Kos Lama', 'tipe' => TipeKos::Campur, 'periode_minimal_bulan' => 12]);

        $this->get("/admin/datakos/{$kos->id}/edit")
            ->assertOk()
            ->assertSee('value="Kos Lama"', false)
            ->assertSee('Tahunan')
            ->assertSee('Campur')
            ->assertSee(number_format($kos->lokasi->lat, 7, '.', ''));
    }

    public function test_update_hapus_foto_lama_tambah_foto_baru_dan_ganti_utama(): void
    {
        $this->post('/admin/datakos', $this->formLengkap());
        $kos = Kos::firstOrFail();
        [$fotoA, $fotoB] = FotoKos::where('kos_id', $kos->id)->orderBy('urutan')->get()->all();

        $this->put("/admin/datakos/{$kos->id}", $this->formLengkap([
            'nama_kos' => 'Kos Uji Diubah',
            'foto' => [UploadedFile::fake()->image('baru.jpg')],
            'hapus_foto' => [(string) $fotoB->id],
            'foto_utama' => "lama:{$fotoA->id}",
        ]))->assertRedirect(route('admin.datakos.detail', $kos))->assertSessionHasNoErrors();

        $kos->refresh();
        $this->assertSame('Kos Uji Diubah', $kos->nama);
        $this->assertSame(2, $kos->foto()->count());
        $this->assertNull(FotoKos::find($fotoB->id));
        Storage::disk('public')->assertMissing($fotoB->path);
        $this->assertSame($fotoA->id, $kos->fotoUtama->id);
    }

    public function test_tidak_bisa_menghapus_foto_milik_kos_lain(): void
    {
        $kosLain = $this->kosLengkap();
        $kos = $this->kosLengkap();
        $fotoLain = $kosLain->foto()->first();

        $this->put("/admin/datakos/{$kos->id}", ['nama_kos' => 'X', 'hapus_foto' => [(string) $fotoLain->id]])
            ->assertSessionHasErrors('hapus_foto.0');

        $this->assertNotNull(FotoKos::find($fotoLain->id));
    }

    public function test_kos_aktif_tidak_boleh_disimpan_tanpa_lokasi(): void
    {
        $kos = $this->kosLengkap(['status' => StatusKos::Aktif, 'nama' => 'Kos Aktif']);

        $this->from("/admin/datakos/{$kos->id}/edit")
            ->put("/admin/datakos/{$kos->id}", $this->formLengkap([
                'nama_kos' => 'Kos Aktif',
                'latitude' => '',
                'longitude' => '',
                'foto' => [],
            ]))
            ->assertRedirect("/admin/datakos/{$kos->id}/edit")
            ->assertSessionHasErrors('status');

        $this->assertNotNull($kos->fresh()->lokasi, 'Transaksi dibatalkan, lokasi lama tetap ada');
    }

    // ---------- Hapus ----------

    public function test_hapus_kos_menghapus_data_dan_file_foto(): void
    {
        $this->post('/admin/datakos', $this->formLengkap());
        $kos = Kos::firstOrFail();
        $path = $kos->foto()->first()->path;

        $this->delete("/admin/datakos/{$kos->id}")
            ->assertRedirect('/admin/datakos')
            ->assertSessionHas('success');

        $this->assertNull(Kos::find($kos->id));
        $this->assertSame(0, FotoKos::count());
        Storage::disk('public')->assertMissing($path);
    }

    // ---------- Status ----------

    public function test_kos_tidak_lengkap_tidak_bisa_diaktifkan(): void
    {
        $kos = Kos::create(['nama' => 'Kos Belum Lengkap']);

        $this->from('/admin/datakos')
            ->patch("/admin/datakos/{$kos->id}/status", ['status' => 'aktif'])
            ->assertRedirect('/admin/datakos')
            ->assertSessionHasErrors('status');

        $this->assertSame(StatusKos::Draft, $kos->fresh()->status);
    }

    public function test_kos_lengkap_bisa_diaktifkan_dan_dikembalikan_ke_draft(): void
    {
        $kos = $this->kosLengkap();

        $this->patch("/admin/datakos/{$kos->id}/status", ['status' => 'aktif'])->assertSessionHasNoErrors();
        $this->assertSame(StatusKos::Aktif, $kos->fresh()->status);

        $this->patch("/admin/datakos/{$kos->id}/status", ['status' => 'draft'])->assertSessionHasNoErrors();
        $this->assertSame(StatusKos::Draft, $kos->fresh()->status);
    }

    public function test_status_yang_tidak_dikenal_ditolak(): void
    {
        $kos = $this->kosLengkap();

        $this->patch("/admin/datakos/{$kos->id}/status", ['status' => 'terbit'])->assertSessionHasErrors('status');
    }
}
