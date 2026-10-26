<?php

namespace App\Http\Controllers\Api;

use App\Enums\StatusKos;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FilterKosRequest;
use App\Http\Resources\KosResource;
use App\Models\Kos;
use App\Services\PencarianKosService;
use App\Services\Routing\RuteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class KosController extends Controller
{
    /** Satu IP dihitung sekali per kos dalam rentang ini. */
    private const JEDA_HITUNG_VIEW_JAM = 6;

    public function index(FilterKosRequest $request, PencarianKosService $pencarian): JsonResponse
    {
        $filter = $request->validated();
        $hasil = $pencarian->cari($filter);

        return response()->json([
            'data' => KosResource::collection($hasil)->resolve(),
            'meta' => [
                'jumlah' => $hasil->count(),
                'urut' => $filter['urut'] ?? 'rekomendasi',
                'bobot' => config('kostin.rekomendasi.bobot'),
            ],
        ]);
    }

    /** Tambah jumlah dilihat saat pengguna membuka detail kos. */
    public function dilihat(Request $request, Kos $kos): JsonResponse
    {
        abort_unless($kos->status === StatusKos::Aktif, 404);

        $kunci = 'dilihat:'.$kos->id.':'.sha1((string) $request->ip());
        $baru = Cache::add($kunci, true, now()->addHours(self::JEDA_HITUNG_VIEW_JAM));

        if ($baru) {
            $kos->increment('jumlah_dilihat');
        }

        return response()->json([
            'jumlah_dilihat' => $kos->fresh()->jumlah_dilihat,
            'dihitung' => $baru,
        ]);
    }

    /** Rute jalan kaki dari kos ke kampus (pgRouting). */
    public function rute(Kos $kos, RuteService $rute): JsonResponse
    {
        abort_unless($kos->status === StatusKos::Aktif, 404);

        return response()->json($rute->untukKos($kos));
    }
}