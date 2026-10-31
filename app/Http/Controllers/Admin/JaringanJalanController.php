<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RekamanJalanRequest;
use App\Models\Kos;
use App\Models\RekamanJalan;
use App\Services\Routing\BacaRekamanGps;
use App\Services\Routing\JaringanSurvei;
use App\Services\Routing\RuteService;
use App\Services\Routing\ValidasiJarak;
use App\Support\Koordinat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman admin Jaringan Jalan (Fase Locality): unggah rekaman gang hasil survei,
 * lihat gang di peta, bandingkan rute OSM dengan rute OSM + gang survei, dan ekspor tabel validasi.
 */
class JaringanJalanController extends Controller
{
    public function index(): View
    {
        $rekaman = RekamanJalan::with('pengunggah:id,name')->latest('id')->get();

        // Gang digambar dari ruas survei (ujungnya sudah menempel ke jalan). Rekaman yang tidak
        // tersambung digambar dari geometri aslinya.
        $garis = collect(DB::select(
            "SELECT rk.id, rk.ujung_tersambung, COALESCE(ST_AsGeoJSON(r.geom, 7), ST_AsGeoJSON(rk.geom, 7)) AS gj
             FROM rekaman_jalan rk
             LEFT JOIN ruas_jalan r ON r.rekaman_id = rk.id AND r.sumber = 'survei'"
        ))->mapWithKeys(fn ($b) => [$b->id => json_decode($b->gj, true)]);

        /** @var \Illuminate\Database\Eloquent\Collection<int, Kos> $daftarKos */
        $daftarKos = Kos::query()->whereNotNull('lokasi')->orderBy('nama')->get();
        $kos = $daftarKos->map(fn (Kos $k) => [
            'id' => $k->id,
            'nama' => $k->nama,
            'status' => $k->status->label(),
            'lat' => $k->lokasi->lat,
            'lng' => $k->lokasi->lng,
            'garis_lurus_m' => (int) round($k->lokasi->jarakMeterKe(Koordinat::kampus())),
        ]);

        return view('admin.jaringanJalan', [
            'rekaman' => $rekaman,
            'garis' => $garis,
            'kos' => $kos,
            'adaJaringanOsm' => DB::table('ruas_jalan')->where('sumber', 'osm')->exists(),
        ]);
    }

    public function store(RekamanJalanRequest $request, BacaRekamanGps $pembaca, JaringanSurvei $survei): RedirectResponse
    {
        $berkas = $request->file('berkas');

        try {
            $titik = $pembaca->baca((string) file_get_contents($berkas->getRealPath()), $berkas->getClientOriginalName());
            $rekaman = $survei->tambah(
                $titik,
                $berkas->getClientOriginalName(),
                $request->validated('nama'),
                $request->validated('catatan'),
                $request->user()?->id,
            );
        } catch (RuntimeException $e) {
            return back()->withErrors(['berkas' => $e->getMessage()])->withInput();
        }

        $ujung = $rekaman->ujung_tersambung === 2
            ? 'kedua ujungnya tersambung ke jalan'
            : 'satu ujung tersambung ke jalan (gang buntu)';

        return redirect()->route('admin.jaringanJalan')->with('success', sprintf(
            '%s tersimpan: %s m, %s. Rute semua kos sudah memakai gang ini bila lebih pendek.',
            $rekaman->namaTampil(), number_format($rekaman->panjang_m, 0, ',', '.'), $ujung,
        ));
    }

    public function destroy(RekamanJalan $rekaman, JaringanSurvei $survei): RedirectResponse
    {
        $nama = $rekaman->namaTampil();
        $survei->hapus($rekaman);

        return redirect()->route('admin.jaringanJalan')->with('success', "{$nama} dihapus. Rute kos dihitung ulang tanpa gang ini.");
    }

    /** Baris tabel validasi satu kos beserta garis kedua rute, untuk tabel dan peta pembanding. */
    public function bandingkan(Kos $kos, ValidasiJarak $validasi, RuteService $rute): JsonResponse
    {
        if (! $kos->lokasi) {
            return response()->json(['pesan' => 'Kos ini belum punya titik lokasi.'], 422);
        }

        return response()->json($validasi->untukKos($kos) + [
            'geometri_osm' => $rute->untukKos($kos, false)['geometri'] ?? null,
            'geometri_survei' => $rute->untukKos($kos, true)['geometri'] ?? null,
        ]);
    }

    /** CSV untuk laporan. Pemisah titik koma dan desimal koma supaya langsung rapi di Excel berbahasa Indonesia. */
    public function ekspor(ValidasiJarak $validasi): StreamedResponse
    {
        // Rute yang belum ada di cache dihitung di sini, jadi beri waktu lebih untuk data banyak.
        set_time_limit(300);
        $baris = $validasi->semua();

        return response()->streamDownload(function () use ($baris) {
            $keluar = fopen('php://output', 'w');
            fwrite($keluar, "\xEF\xBB\xBF");
            fputcsv($keluar, ['No', 'Nama Kos', 'Status', 'Garis Lurus (m)', 'Rute OSM (m)', 'Rute OSM + Survei (m)', 'Selisih (m)', 'Rasio OSM', 'Rasio OSM + Survei'], ';', '"', '');
            foreach ($baris->values() as $i => $b) {
                fputcsv($keluar, [
                    $i + 1, $b['nama'], $b['status'], $b['garis_lurus_m'],
                    $b['rute_osm_m'] ?? '', $b['rute_survei_m'] ?? '', $b['selisih_m'] ?? '',
                    $b['rasio_osm'] !== null ? number_format($b['rasio_osm'], 2, ',', '') : '',
                    $b['rasio_survei'] !== null ? number_format($b['rasio_survei'], 2, ',', '') : '',
                ], ';', '"', '');
            }
            fclose($keluar);
        }, 'validasi-jarak-kostin-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}