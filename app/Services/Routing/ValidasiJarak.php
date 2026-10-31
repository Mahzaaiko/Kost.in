<?php

namespace App\Services\Routing;

use App\Models\Kos;
use App\Support\Koordinat;
use Illuminate\Support\Collection;

/**
 * Perbandingan jarak kos ke kampus untuk halaman admin Jaringan Jalan dan laporan:
 * garis lurus, rute OSM saja, dan rute OSM + gang survei.
 *
 * Rasio = panjang rute / garis lurus (circuity atau detour index). Makin dekat ke 1,
 * makin langsung jalurnya.
 */
class ValidasiJarak
{
    public function __construct(private RuteService $rute) {}

    /**
     * @return array{id: int, nama: string, status: string, garis_lurus_m: int, rute_osm_m: ?int, rute_survei_m: ?int, selisih_m: ?int, rasio_osm: ?float, rasio_survei: ?float}
     */
    public function untukKos(Kos $kos): array
    {
        $garisLurus = $kos->lokasi->jarakMeterKe(Koordinat::kampus());
        $osm = $this->rute->untukKos($kos, false);
        $survei = $this->rute->untukKos($kos, true);

        $panjangOsm = $osm['tersedia'] ? $osm['panjang_m'] : null;
        $panjangSurvei = $survei['tersedia'] ? $survei['panjang_m'] : null;

        return [
            'id' => $kos->id,
            'nama' => $kos->nama,
            'status' => $kos->status->label(),
            'garis_lurus_m' => (int) round($garisLurus),
            'rute_osm_m' => $panjangOsm,
            'rute_survei_m' => $panjangSurvei,
            'selisih_m' => $panjangOsm !== null && $panjangSurvei !== null ? $panjangOsm - $panjangSurvei : null,
            'rasio_osm' => $this->rasio($panjangOsm, $garisLurus),
            'rasio_survei' => $this->rasio($panjangSurvei, $garisLurus),
        ];
    }

    /** Semua kos yang punya titik lokasi, urut nama. */
    public function semua(): Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, Kos> $daftar */
        $daftar = Kos::query()->whereNotNull('lokasi')->orderBy('nama')->get();

        return $daftar->map(fn (Kos $kos) => $this->untukKos($kos));
    }

    private function rasio(?int $rute, float $garisLurus): ?float
    {
        return $rute !== null && $garisLurus >= 1 ? round($rute / $garisLurus, 2) : null;
    }
}