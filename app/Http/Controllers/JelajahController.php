<?php

namespace App\Http\Controllers;

use App\Models\Fasilitas;
use App\Models\Kos;
use App\Support\Koordinat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\View;

class JelajahController extends Controller
{
    public function __invoke(Request $request): View
    {
        $kampus = Koordinat::kampus();

        // Filter fasilitas hanya menampilkan fasilitas yang dimiliki minimal satu kos aktif.
        $fasilitasFilter = Fasilitas::whereHas('kos', fn ($q) => $q->aktif())->orderBy('nama')->get(['id', 'nama', 'ikon']);

        // Ikon fasilitas dirender sekali di server lalu dipakai JavaScript panel detail.
        $ikonSvg = collect(array_keys(Fasilitas::IKON))
            ->push('default')
            ->mapWithKeys(fn ($kunci) => [$kunci => trim(Blade::render('<x-ikon-fasilitas :ikon="$ikon" />', ['ikon' => $kunci === 'default' ? null : $kunci]))]);

        // Rentang slider harga mengikuti data kos aktif, dibulatkan ke 100 ribu.
        $hargaTertinggi = (int) Kos::aktif()->max('harga_per_bulan');
        $hargaMax = max(1_000_000, (int) ceil($hargaTertinggi / 100_000) * 100_000);

        return view('pengguna.jelajahKos', [
            'kampus' => ['nama' => config('kostin.kampus.nama'), 'lat' => $kampus->lat, 'lng' => $kampus->lng],
            'fasilitasFilter' => $fasilitasFilter,
            'ikonSvg' => $ikonSvg,
            'hargaMin' => 100_000,
            'hargaMax' => $hargaMax,
            'jarakMaks' => (int) config('kostin.jarak_maks_km'),
            'awal' => [
                'q' => mb_substr((string) $request->query('q', ''), 0, 100),
                'kos' => $request->integer('kos') ?: null,
            ],
        ]);
    }
}