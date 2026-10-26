<?php

namespace App\Http\Resources;

use App\Models\Kos;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Bentuk data satu kos untuk halaman pengguna (daftar, marker peta, panel detail).
 *
 * @mixin Kos
 */
class KosResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'tipe' => $this->tipe?->label(),
            'harga' => $this->harga_per_bulan,
            'periode' => $this->labelPeriode(),
            'telepon' => $this->no_telepon,
            'alamat' => $this->alamat,
            'deskripsi' => $this->deskripsi,
            'fasilitas' => $this->fasilitas->map(fn ($f) => [
                'id' => $f->id,
                'nama' => $f->nama,
                'ikon' => $f->ikon,
            ])->values(),
            'fotos' => $this->foto->map(fn ($f) => $f->url())->values(),
            'lat' => $this->lokasi?->lat,
            'lng' => $this->lokasi?->lng,
            'jarak_m' => $this->jarak_m !== null ? (int) round((float) $this->jarak_m) : null,
            'skor' => $this->skor,
        ];
    }
}