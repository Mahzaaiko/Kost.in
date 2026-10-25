<?php

namespace Tests\Feature;

use App\Enums\StatusKos;
use App\Enums\TipeKos;
use App\Models\Fasilitas;
use App\Models\FotoKos;
use App\Models\Kos;
use App\Support\Koordinat;
use Database\Seeders\FasilitasSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KosModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_lokasi_tersimpan_dengan_urutan_lat_lng_yang_benar(): void
    {
        $kos = Kos::create([
            'nama' => 'Kos Uji',
            'lokasi' => new Koordinat(lat: -6.5895875, lng: 106.8067344),
        ]);

        $baris = DB::selectOne('SELECT ST_Y(lokasi::geometry) AS lat, ST_X(lokasi::geometry) AS lng, ST_SRID(lokasi::geometry) AS srid FROM kos WHERE id = ?', [$kos->id]);

        $this->assertEqualsWithDelta(-6.5895875, $baris->lat, 1e-7);
        $this->assertEqualsWithDelta(106.8067344, $baris->lng, 1e-7);
        $this->assertSame(4326, (int) $baris->srid);
    }

    public function test_lokasi_terbaca_kembali_sebagai_koordinat(): void
    {
        $kos = Kos::create(['nama' => 'Kos Uji', 'lokasi' => new Koordinat(lat: -6.59, lng: 106.81)]);

        // Sebelum refresh (masih ekspresi SQL) dan sesudah refresh (EWKB dari database).
        $this->assertEqualsWithDelta(-6.59, $kos->lokasi->lat, 1e-9);

        $segar = Kos::findOrFail($kos->id);
        $this->assertInstanceOf(Koordinat::class, $segar->lokasi);
        $this->assertEqualsWithDelta(-6.59, $segar->lokasi->lat, 1e-7);
        $this->assertEqualsWithDelta(106.81, $segar->lokasi->lng, 1e-7);
    }

    public function test_lokasi_boleh_kosong_untuk_draft(): void
    {
        $kos = Kos::create(['nama' => 'Kos Tanpa Titik']);

        $this->assertNull($kos->fresh()->lokasi);
        $this->assertSame(StatusKos::Draft, $kos->fresh()->status);
    }

    public function test_jarak_ke_kampus_dihitung_postgis_dalam_meter(): void
    {
        $kampus = Koordinat::kampus();
        $kos = Kos::create([
            'nama' => 'Kos 111 m',
            'lokasi' => new Koordinat(lat: $kampus->lat + 0.001, lng: $kampus->lng),
        ]);

        $jarak = DB::scalar(
            'SELECT ST_Distance(lokasi, ST_GeogFromText(?)) FROM kos WHERE id = ?',
            [$kampus->toEwkt(), $kos->id],
        );

        $this->assertEqualsWithDelta(110.6, (float) $jarak, 1.0);
    }

    public function test_enum_dan_relasi_fasilitas_serta_foto(): void
    {
        $this->seed(FasilitasSeeder::class);
        $kos = Kos::factory()->create(['tipe' => TipeKos::Putri, 'periode_minimal_bulan' => 12]);

        $kos->fasilitas()->attach(Fasilitas::whereIn('nama', ['WiFi', 'AC'])->pluck('id'));
        FotoKos::create(['kos_id' => $kos->id, 'path' => 'kos/a.jpg', 'urutan' => 1]);
        FotoKos::create(['kos_id' => $kos->id, 'path' => 'kos/b.jpg', 'urutan' => 2, 'is_utama' => true]);

        $kos = $kos->fresh(['fasilitas', 'foto', 'fotoUtama']);

        $this->assertSame(TipeKos::Putri, $kos->tipe);
        $this->assertSame('Tahunan', $kos->labelPeriode());
        $this->assertSame(['AC', 'WiFi'], $kos->fasilitas->pluck('nama')->all());
        $this->assertSame('kos/b.jpg', $kos->foto->first()->path, 'Foto utama tampil paling depan');
        $this->assertSame('kos/b.jpg', $kos->fotoUtama->path);
    }

    public function test_scope_aktif_hanya_mengambil_kos_aktif(): void
    {
        Kos::factory()->count(2)->create();
        Kos::factory()->aktif()->count(3)->create();

        $this->assertSame(3, Kos::aktif()->count());
    }

    public function test_database_menolak_dua_foto_utama_dalam_satu_kos(): void
    {
        $kos = Kos::factory()->create();
        FotoKos::create(['kos_id' => $kos->id, 'path' => 'a.jpg', 'is_utama' => true]);

        $this->expectException(QueryException::class);
        FotoKos::create(['kos_id' => $kos->id, 'path' => 'b.jpg', 'is_utama' => true]);
    }

    public function test_database_menolak_periode_di_luar_pilihan(): void
    {
        $this->expectException(QueryException::class);
        Kos::create(['nama' => 'Kos Periode Aneh', 'periode_minimal_bulan' => 2]);
    }

    public function test_hapus_kos_ikut_menghapus_foto_dan_relasi_fasilitas(): void
    {
        $this->seed(FasilitasSeeder::class);
        $kos = Kos::factory()->create();
        $kos->fasilitas()->attach(Fasilitas::first()->id);
        FotoKos::create(['kos_id' => $kos->id, 'path' => 'a.jpg']);

        $kos->delete();

        $this->assertSame(0, DB::table('fasilitas_kos')->count());
        $this->assertSame(0, FotoKos::count());
    }
}
