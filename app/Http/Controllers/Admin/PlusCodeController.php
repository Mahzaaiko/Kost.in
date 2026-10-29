<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Koordinat;
use App\Support\PlusCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ubah Plus Code (mis. "CR66+V3" yang disalin dari Google Maps) menjadi koordinat
 * untuk form Tambah/Edit kos. Kode pendek dipulihkan dengan titik kampus sebagai acuan.
 */
class PlusCodeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(['kode' => ['required', 'string', 'max:150']]);

        $kode = PlusCode::cariDiTeks($request->string('kode'));
        $titik = $kode ? PlusCode::keKoordinat($kode, Koordinat::kampus()) : null;

        if (! $titik) {
            return response()->json(['pesan' => 'Plus Code tidak dikenali. Contoh: CR66+V3'], 422);
        }

        $batasKm = (float) config('kostin.radius_validasi_km');
        if ($titik->jarakMeterKe(Koordinat::kampus()) > $batasKm * 1000) {
            return response()->json(['pesan' => "Plus Code {$kode} berada lebih dari {$batasKm} km dari kampus. Periksa lagi kodenya."], 422);
        }

        return response()->json([
            'kode' => $kode,
            'lat' => round($titik->lat, 7),
            'lng' => round($titik->lng, 7),
            'presisi_m' => (int) round(PlusCode::presisiMeter($kode)),
        ]);
    }
}