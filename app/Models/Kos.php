<?php

namespace App\Models;

use App\Casts\GeographyPoint;
use App\Enums\StatusKos;
use App\Enums\TipeKos;
use App\Support\Koordinat;
use Database\Factories\KosFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $nama
 * @property TipeKos|null $tipe
 * @property int|null $harga_per_bulan
 * @property int|null $periode_minimal_bulan
 * @property Koordinat|null $lokasi
 * @property StatusKos $status
 */
class Kos extends Model
{
    /** @use HasFactory<KosFactory> */
    use HasFactory;

    protected $table = 'kos';

    protected $fillable = [
        'nama',
        'nama_pemilik',
        'no_telepon',
        'alamat',
        'deskripsi',
        'tipe',
        'harga_per_bulan',
        'periode_minimal_bulan',
        'lokasi',
        'status',
        'catatan_import',
    ];

    protected $attributes = [
        'status' => 'draft',
        'jumlah_dilihat' => 0,
    ];

    protected function casts(): array
    {
        return [
            'tipe' => TipeKos::class,
            'status' => StatusKos::class,
            'harga_per_bulan' => 'integer',
            'periode_minimal_bulan' => 'integer',
            'jumlah_dilihat' => 'integer',
            'lokasi' => GeographyPoint::class,
        ];
    }

    public function fasilitas(): BelongsToMany
    {
        return $this->belongsToMany(Fasilitas::class, 'fasilitas_kos', 'kos_id', 'fasilitas_id')
            ->orderBy('fasilitas.nama');
    }

    public function foto(): HasMany
    {
        return $this->hasMany(FotoKos::class, 'kos_id')->orderByDesc('is_utama')->orderBy('urutan');
    }

    public function fotoUtama(): HasOne
    {
        return $this->hasOne(FotoKos::class, 'kos_id')->where('is_utama', true);
    }

    /** Hanya kos yang boleh tampil di halaman pengguna. */
    #[Scope]
    protected function aktif(Builder $query): void
    {
        $query->where('status', StatusKos::Aktif->value);
    }

    /** Label periode minimal untuk UI, mengikuti pilihan di form admin. */
    public function labelPeriode(): ?string
    {
        return match ($this->periode_minimal_bulan) {
            1 => '1 Bulan',
            3 => '3 Bulan',
            6 => '6 Bulan',
            12 => 'Tahunan',
            default => null,
        };
    }
}
