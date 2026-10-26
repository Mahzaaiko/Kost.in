<?php

namespace App\Http\Controllers;

use App\Models\Kos;
use App\Services\PencarianKosService;
use App\Support\Format;
use App\Support\Koordinat;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(PencarianKosService $pencarian): View
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Kos> $semuaKos */
        $semuaKos = $pencarian->queryDasar()->with(['fasilitas', 'fotoUtama'])->get();

        // "Kos yang sering dilihat": 3 kos aktif dengan jumlah dilihat terbanyak.
        $kosKartu = $semuaKos
            ->sortBy([['jumlah_dilihat', 'desc'], ['jarak_m', 'asc']])
            ->take(3)
            ->map(fn (Kos $k) => [
                'id' => $k->id,
                'nama' => $k->nama,
                'tipe' => $k->tipe?->label(),
                'jarak' => Format::jarak((float) $k->jarak_m),
                'fasilitas' => $k->fasilitas->take(3)->pluck('nama')->all(),
                'harga_fmt' => Format::rupiah($k->harga_per_bulan),
                'foto' => $k->fotoUtama?->url(),
            ])
            ->values();

        // Titik untuk peta radius di Beranda.
        $kosList = $semuaKos->map(fn (Kos $k) => [
            'nama' => $k->nama,
            'tipe' => $k->tipe?->label(),
            'lat' => $k->lokasi->lat,
            'lng' => $k->lokasi->lng,
            'jarak_m' => (int) round((float) $k->jarak_m),
            'jarak' => Format::jarak((float) $k->jarak_m),
        ])->values();

        $kampus = Koordinat::kampus();

        return view('pengguna.beranda', [
            'kampus' => ['nama' => config('kostin.kampus.nama'), 'lat' => $kampus->lat, 'lng' => $kampus->lng],
            'kosKartu' => $kosKartu,
            'kosList' => $kosList,
            'jumlahKos' => $semuaKos->count(),
        ]);
    }
}