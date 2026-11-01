<?php

namespace Database\Seeders;

use App\Models\Fasilitas;
use Illuminate\Database\Seeder;

/**
 * Master fasilitas hasil pembakuan dari spreadsheet pendataan kos.
 * Kolom "ikon" adalah kunci ikon di komponen x-ikon-fasilitas (App\Models\Fasilitas::IKON).
 */
class FasilitasSeeder extends Seeder
{
    /** @var array<string, string|null> nama => kunci ikon */
    public const DAFTAR = [
        'Kamar Mandi Dalam' => 'kamar-mandi',
        'Kamar Mandi Luar' => 'kamar-mandi-luar',
        'Water Heater' => 'water-heater',
        'Kasur' => 'kasur',
        'Lemari' => 'lemari',
        'Meja' => 'meja',
        'Kursi' => 'kursi',
        'AC' => 'ac',
        'Kipas Angin' => 'kipas',
        'WiFi' => 'wifi',
        'Dapur' => 'dapur',
        'Kulkas' => 'kulkas',
        'Dispenser' => 'dispenser',
        'Mesin Cuci' => 'mesin-cuci',
        'Laundry' => 'laundry',
        'Tempat Jemur' => 'jemur',
        'Ruang TV' => 'tv',
        'Ruang Tamu' => 'ruang-tamu',
        'Parkir Motor' => 'motor',
        'Parkir Mobil' => 'mobil',
        'CCTV' => 'cctv',
        'Mushola' => 'mushola',
        'Listrik Termasuk' => 'listrik',
        'Air Termasuk' => 'air',
    ];

    public function run(): void
    {
        foreach (self::DAFTAR as $nama => $ikon) {
            Fasilitas::updateOrCreate(['nama' => $nama], ['ikon' => $ikon]);
        }
    }
}