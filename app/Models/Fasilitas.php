<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Fasilitas extends Model
{
    protected $table = 'fasilitas';

    protected $fillable = ['nama', 'ikon'];

    /** Pilihan ikon yang tersedia di komponen x-ikon-fasilitas. */
    public const IKON = [
        'wifi' => 'WiFi',
        'ac' => 'AC',
        'kipas' => 'Kipas angin',
        'kamar-mandi' => 'Kamar mandi dalam',
        'kamar-mandi-luar' => 'Kamar mandi luar',
        'water-heater' => 'Water heater',
        'kasur' => 'Kasur',
        'lemari' => 'Lemari',
        'meja' => 'Meja',
        'kursi' => 'Kursi',
        'dapur' => 'Dapur',
        'kulkas' => 'Kulkas',
        'dispenser' => 'Dispenser',
        'mesin-cuci' => 'Mesin cuci',
        'laundry' => 'Laundry',
        'jemur' => 'Tempat jemur',
        'tv' => 'Ruang TV',
        'ruang-tamu' => 'Ruang tamu',
        'parkir' => 'Parkir',
        'motor' => 'Parkir motor',
        'mobil' => 'Parkir mobil',
        'keamanan' => 'Keamanan',
        'cctv' => 'CCTV',
        'mushola' => 'Mushola',
        'listrik' => 'Listrik',
        'air' => 'Air',
    ];

    public function kos(): BelongsToMany
    {
        return $this->belongsToMany(Kos::class, 'fasilitas_kos', 'fasilitas_id', 'kos_id');
    }
}