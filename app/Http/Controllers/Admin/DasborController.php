<?php

namespace App\Http\Controllers\Admin;

use App\Enums\StatusKos;
use App\Http\Controllers\Controller;
use App\Models\Fasilitas;
use App\Models\Kos;
use App\Support\Koordinat;
use Illuminate\View\View;

class DasborController extends Controller
{
    public function index(): View
    {
        $total = Kos::count();
        $aktif = Kos::aktif()->count();
        $bulanIni = Kos::where('created_at', '>=', now()->startOfMonth())->count();
        $terpetakan = Kos::whereNotNull('lokasi')->count();
        $belumTerpetakan = $total - $terpetakan;

        $stats = [
            ['label' => 'Total Kos', 'value' => $total, 'unit' => 'kos', 'note' => "+{$bulanIni} bulan ini", 'icon' => 'building', 'primary' => true],
            ['label' => 'Kos Aktif', 'value' => $aktif, 'unit' => 'kos', 'note' => $total > 0 ? number_format($aktif / $total * 100, 1, ',', '.').'% dari total' : 'Belum ada data', 'icon' => 'check'],
            ['label' => 'Data Fasilitas', 'value' => Fasilitas::count(), 'unit' => 'fasilitas', 'note' => 'Jenis fasilitas', 'icon' => 'sparkles'],
            ['label' => 'Lokasi Terdaftar', 'value' => $terpetakan, 'unit' => 'lokasi', 'note' => $belumTerpetakan > 0 ? "{$belumTerpetakan} kos belum terpetakan" : 'Semua terpetakan', 'icon' => 'pin'],
        ];

        $kosTerbaru = Kos::with('fotoUtama')->latest('updated_at')->latest('id')->limit(4)->get();

        // Titik untuk peta persebaran (draft ikut ditampilkan, dibedakan warnanya).
        $titikKos = Kos::whereNotNull('lokasi')
            ->get(['id', 'nama', 'status', 'lokasi'])
            ->map(fn (Kos $k) => [
                'nama' => $k->nama,
                'aktif' => $k->status === StatusKos::Aktif,
                'lat' => $k->lokasi->lat,
                'lng' => $k->lokasi->lng,
                'url' => route('admin.datakos.detail', $k),
            ])
            ->values();

        return view('admin.dasbor', [
            'stats' => $stats,
            'kosTerbaru' => $kosTerbaru,
            'titikKos' => $titikKos,
            'kampus' => Koordinat::kampus(),
        ]);
    }
}
