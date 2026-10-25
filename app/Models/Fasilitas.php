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
        'kamar-mandi' => 'Kamar mandi',
        'parkir' => 'Parkir',
        'dapur' => 'Dapur',
        'laundry' => 'Laundry',
        'keamanan' => 'Keamanan / CCTV',
    ];

    public function kos(): BelongsToMany
    {
        return $this->belongsToMany(Kos::class, 'fasilitas_kos', 'fasilitas_id', 'kos_id');
    }
}
