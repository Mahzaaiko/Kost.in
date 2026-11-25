<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    /** Disk penyimpanan foto: "public" di lokal, "s3" (Neon Object Storage) di server. */
    public static function disk(): string
    {
        return config('kostin.foto_disk', 'public');
    }

    /**
     * URL publik file foto.
     * Disk "public": pakai asset() supaya mengikuti host yang sedang dibuka (butuh storage:link).
     * Disk lain (s3): URL dari konfigurasi disk (AWS_URL).
     */
    public function url(): string
    {
        if (self::disk() === 'public') {
            return asset('storage/'.$this->path);
        }

        return Storage::disk(self::disk())->url($this->path);
    }
}
