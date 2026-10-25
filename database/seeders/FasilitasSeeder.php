<?php

namespace Database\Seeders;

use App\Models\Fasilitas;
use Illuminate\Database\Seeder;

/**
 * Master fasilitas hasil pembakuan dari spreadsheet pendataan kos.
 * Kolom "ikon" adalah kunci ikon SVG yang sudah ada di frontend;
 * null berarti memakai ikon default.
 */
class FasilitasSeeder extends Seeder
{
    /** @var array<string, string|null> nama => kunci ikon */
    public const DAFTAR = [
        'Kamar Mandi Dalam' => 'kamar-mandi',
        'Kamar Mandi Luar' => null,
        'Water Heater' => null,
        'Kasur' => null,
        'Lemari' => null,
        'Meja' => null,
        'Kursi' => null,
        'AC' => 'ac',
        'Kipas Angin' => null,
        'WiFi' => 'wifi',
        'Dapur' => 'dapur',
        'Kulkas' => null,
        'Dispenser' => null,
        'Mesin Cuci' => null,
        'Laundry' => 'laundry',
        'Tempat Jemur' => null,
        'Ruang TV' => null,
        'Ruang Tamu' => null,
        'Parkir Motor' => 'parkir',
        'Parkir Mobil' => 'parkir',
        'CCTV' => 'keamanan',
        'Mushola' => null,
        'Listrik Termasuk' => null,
        'Air Termasuk' => null,
    ];

    public function run(): void
    {
        foreach (self::DAFTAR as $nama => $ikon) {
            Fasilitas::updateOrCreate(['nama' => $nama], ['ikon' => $ikon]);
        }
    }
}
