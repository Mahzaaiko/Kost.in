<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\Kos;
use App\Support\Koordinat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        try {
            $totalKos = Kos::count();
            $kosAktif = Kos::aktif()->count();
            $kampus = Koordinat::kampus();
            $titikKos = Kos::aktif()
                ->whereNotNull('lokasi')
                ->get(['id', 'nama', 'status', 'lokasi'])
                ->map(fn (Kos $k) => [
                    'nama' => $k->nama,
                    'lat' => $k->lokasi->lat,
                    'lng' => $k->lokasi->lng,
                ])
                ->values();
        } catch (\Throwable $e) {
            $totalKos = 83;
            $kosAktif = 23;
            $kampus = (object) ['lat' => (float) config('kostin.kampus.lat', -6.5888), 'lng' => (float) config('kostin.kampus.lng', 106.8062)];
            $titikKos = collect();
        }

        return view('admin.login', compact('totalKos', 'kosAktif', 'kampus', 'titikKos'));
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dasbor'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
