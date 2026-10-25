<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Fasilitas extends Model
{
    protected $table = 'fasilitas';

    protected $fillable = ['nama', 'ikon'];

    public function kos(): BelongsToMany
    {
        return $this->belongsToMany(Kos::class, 'fasilitas_kos', 'fasilitas_id', 'kos_id');
    }
}
