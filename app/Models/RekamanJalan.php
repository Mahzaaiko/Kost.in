<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rekaman gang hasil survei lapangan. Geometri (kolom geom) dan ruas jalan turunannya
 * (ruas_jalan.rekaman_id) dikelola lewat App\Services\Routing\JaringanSurvei.
 */
class RekamanJalan extends Model
{
    protected $table = 'rekaman_jalan';

    protected $fillable = ['nama', 'catatan', 'nama_file', 'jumlah_titik', 'panjang_m', 'ujung_tersambung', 'diunggah_oleh'];

    protected $hidden = ['geom'];

    protected function casts(): array
    {
        return [
            'jumlah_titik' => 'integer',
            'panjang_m' => 'float',
            'ujung_tersambung' => 'integer',
        ];
    }

    public function pengunggah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }

    public function namaTampil(): string
    {
        return $this->nama ?: 'Gang #'.$this->id;
    }
}