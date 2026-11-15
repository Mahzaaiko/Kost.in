<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Routing\JangkauanJalanKaki;
use App\Services\Routing\LayerJaringanJalan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Layer tematik peta Jelajah dalam format GeoJSON. */
class PetaController extends Controller
{
    /**
     * Jangkauan jalan kaki dari kampus (isochrone). ?jaringan=osm untuk pembanding tanpa gang survei.
     *
     * Dengan ?jarak_km=1.2: satu area yang bisa dicapai dengan berjalan 1,2 km lewat jalan, untuk
     * slider jarak di Jelajah (dibulatkan ke 0,1 km supaya cache terpakai ulang).
     * Tanpa jarak_km: pita waktu dari config kostin.peta.jangkauan_menit.
     */
    public function jangkauan(Request $request, JangkauanJalanKaki $jangkauan): JsonResponse
    {
        $data = $request->validate([
            'jarak_km' => ['nullable', 'numeric', 'min:0.1', 'max:'.config('kostin.jarak_maks_km')],
        ], [
            'jarak_km.min' => 'Jarak minimal 0,1 km.',
            'jarak_km.max' => 'Jarak maksimal '.config('kostin.jarak_maks_km').' km.',
        ]);
        $jaringan = $request->query('jaringan') === 'osm' ? 'osm' : 'lengkap';

        if (isset($data['jarak_km'])) {
            return response()->json($jangkauan->untukJarak(round((float) $data['jarak_km'], 1), $jaringan));
        }

        return response()->json($jangkauan->dariKampus($jaringan));
    }

    /** Jaringan jalan di sekitar kampus: jalan OSM dan gang hasil survei. */
    public function jaringanJalan(LayerJaringanJalan $layer): JsonResponse
    {
        return response()->json($layer->sekitarKampus());
    }
}
