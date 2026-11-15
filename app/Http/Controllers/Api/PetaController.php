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
    /** Jangkauan jalan kaki dari kampus (isochrone). ?jaringan=osm untuk pembanding tanpa gang survei. */
    public function jangkauan(Request $request, JangkauanJalanKaki $jangkauan): JsonResponse
    {
        $jaringan = $request->query('jaringan') === 'osm' ? 'osm' : 'lengkap';

        return response()->json($jangkauan->dariKampus($jaringan));
    }

    /** Jaringan jalan di sekitar kampus: jalan OSM dan gang hasil survei. */
    public function jaringanJalan(LayerJaringanJalan $layer): JsonResponse
    {
        return response()->json($layer->sekitarKampus());
    }
}