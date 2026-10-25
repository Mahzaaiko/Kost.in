<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FotoKos extends Model
{
    protected $table = 'foto_kos';

    protected $fillable = ['kos_id', 'path', 'is_utama', 'urutan'];

    protected function casts(): array
    {
        return [
            'is_utama' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function kos(): BelongsTo
    {
        return $this->belongsTo(Kos::class, 'kos_id');
    }

    /**
     * URL publik file foto (disk "public", butuh php artisan storage:link).
     * Pakai asset() supaya mengikuti host yang sedang dibuka, bukan APP_URL.
     */
    public function url(): string
    {
        return asset('storage/'.$this->path);
    }
}
