<?php

namespace Tests\Unit;

use App\Enums\TipeKos;
use App\Services\Import\NormalisasiKos as N;
use App\Support\Koordinat;
use Database\Seeders\FasilitasSeeder;
use PHPUnit\Framework\TestCase;

class NormalisasiKosTest extends TestCase
{
    public function test_telepon_dipulihkan_ke_format_08(): void
    {
        $this->assertSame('081314287700', N::telepon('081314287700'));
        $this->assertSame('081314287700', N::telepon('81314287700.0'));
        $this->assertSame('085888676252', N::telepon('+62 858-8867-6252'));
        $this->assertSame('081356179922', N::telepon('62 813-5617-9922'));
        $this->assertSame('081574743732', N::telepon('815-7474-3732'));
        $this->assertSame('02150996923', N::telepon('62 21-509-96923'));
        $this->assertNull(N::telepon(''));
        $this->assertNull(N::telepon('123'));
    }

    public function test_jenis_kos_ke_tipe(): void
    {
        $this->assertSame(TipeKos::Putri, N::tipe('Wanita'));
        $this->assertSame(TipeKos::Putra, N::tipe('Laki-Laki'));
        $this->assertSame(TipeKos::Putra, N::tipe('Laki'));
        $this->assertSame(TipeKos::Campur, N::tipe('Campur'));
        $this->assertNull(N::tipe(''));
    }

    public function test_periode_mengambil_yang_terpendek(): void
    {
        $this->assertSame(1, N::periodeBulan('Per 1 Bulan'));
        $this->assertSame(1, N::periodeBulan('Per bulan'));
        $this->assertSame(3, N::periodeBulan('Per 3 Bulan'));
        $this->assertSame(12, N::periodeBulan('Per Tahun'));
        $this->assertSame(12, N::periodeBulan('12 Bulan'));
        $this->assertSame(3, N::periodeBulan('Per 3 Bulan, 6 bulan, Tahun'));
        $this->assertNull(N::periodeBulan(''));
    }

    public function test_harga_bulanan_tetap(): void
    {
        $this->assertSame(['harga' => 910000, 'catatan' => []], N::harga('910000', 1));
        $this->assertSame(1800000, N::harga('1800000.2', 1)['harga']);
        $this->assertSame(1000000, N::harga('1000000', 12)['harga'], 'Per tahun tapi di bawah 3 juta dianggap sudah per bulan');
    }

    public function test_harga_tahunan_dibagi_12_dan_dicatat(): void
    {
        $hasil = N::harga('12500000', 12);

        $this->assertSame(1042000, $hasil['harga']);
        $this->assertStringContainsString('Rp12.500.000 dianggap per tahun', $hasil['catatan'][0]);
        $this->assertStringContainsString('mohon dicek', N::harga('10000000', null)['catatan'][0]);
    }

    public function test_beberapa_harga_ambil_termurah(): void
    {
        $hasil = N::harga('1250000, 1200000, 1450000, 1350000', 3);

        $this->assertSame(1200000, $hasil['harga']);
        $this->assertStringContainsString('Dipakai yang termurah', $hasil['catatan'][0]);
    }

    public function test_fasilitas_dibakukan_ke_master(): void
    {
        $hasil = N::fasilitas('Kamar Mandi Dalam(water heater), K. Mandi Luar, WIFI, Lemari / Storage, DapurRuang Tamu, Laundry (gratis), Listrik, Air, Taman');

        $this->assertSame(
            ['Kamar Mandi Dalam', 'Water Heater', 'Kamar Mandi Luar', 'WiFi', 'Lemari', 'Dapur', 'Ruang Tamu', 'Laundry', 'Listrik Termasuk', 'Air Termasuk'],
            $hasil['fasilitas'],
        );
        $this->assertSame(['Taman'], $hasil['lainnya']);
    }

    public function test_semua_target_peta_fasilitas_ada_di_master_seeder(): void
    {
        $master = array_keys(FasilitasSeeder::DAFTAR);

        foreach (N::PETA_FASILITAS as $kunci => $target) {
            foreach ($target as $nama) {
                $this->assertContains($nama, $master, "Peta '{$kunci}' menunjuk fasilitas yang tidak ada: {$nama}");
            }
        }
    }

    public function test_alamat_dengan_plus_code(): void
    {
        $kampus = new Koordinat(lat: -6.5895875, lng: 106.8067344);
        $hasil = N::alamat('CRC6+8VF, RT.04/RW.02, Tegal Gundil, Bogor Utara', $kampus);

        $this->assertSame('RT.04/RW.02, Tegal Gundil, Bogor Utara', $hasil['alamat']);
        $this->assertSame('CRC6+8VF', $hasil['plus_code']);
        $this->assertEqualsWithDelta(-6.579187, $hasil['lokasi']->lat, 2e-6);

        $biasa = N::alamat('Alamat: Jl. Malabar Ujung No.7', $kampus);
        $this->assertSame('Jl. Malabar Ujung No.7', $biasa['alamat']);
        $this->assertNull($biasa['lokasi']);
    }

    public function test_nama_dirapikan(): void
    {
        $this->assertSame('Red Brick House Bogor Tengah', N::nama('Red Brick House Bogor Tengah /  '));
        $this->assertNull(N::nama('   '));
    }
}
