<?php

namespace App\Services\Routing;

use App\Models\Kos;
use App\Support\Koordinat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Rute jalan kaki dari titik kos ke kampus dengan pgRouting (pgr_dijkstra).
 *
 * Cara kerja:
 * 1. Titik kos dan titik kampus ditempelkan ke ruas jalan utama terdekat (titik proyeksi di ruas).
 * 2. pgr_dijkstra dihitung dari kedua ujung ruas kos ke kedua ujung ruas kampus (4 kombinasi),
 *    lalu dipilih total terpendek termasuk potongan ruas di awal dan akhir.
 * 3. Garis rute = titik kos → jalan → ... → jalan → titik kampus.
 *
 * Graf dianggap tidak berarah karena pejalan kaki boleh melawan arah jalan satu arah.
 *
 * Secara bawaan rute memakai seluruh jaringan: jalan OSM, gang hasil survei, dan ruas sambungannya
 * (ruas yang sudah dipotong sambungan diganti potongannya). Dengan $pakaiSurvei = false hanya ruas
 * OSM asli yang dipakai, untuk tabel validasi di halaman admin.
 */
class RuteService
{
    /** Syarat ruas yang dipakai perhitungan yang sedang berjalan (jaringan lengkap atau OSM saja). */
    private string $syaratRuas = "r.utama AND r.sumber = 'osm'";

    /** @return array{tersedia: bool, alasan?: string, panjang_m?: int, durasi_menit?: int, jarak_ke_jalan_m?: int, catatan?: string|null, geometri?: array} */
    public function untukKos(Kos $kos, bool $pakaiSurvei = true): array
    {
        if (! $kos->lokasi) {
            return ['tersedia' => false, 'alasan' => 'Titik lokasi kos belum diisi.'];
        }

        $kampus = Koordinat::kampus();
        $kunci = sprintf(
            'rute:%s:%.7F,%.7F:%.7F,%.7F%s',
            Cache::get(ImportJaringanJalan::KUNCI_VERSI, '0'),
            $kos->lokasi->lat, $kos->lokasi->lng, $kampus->lat, $kampus->lng,
            $pakaiSurvei ? '' : ':osm',
        );

        return Cache::remember(
            $kunci,
            now()->addDays((int) config('kostin.routing.cache_hari')),
            fn () => $this->hitung($kos->lokasi, $kampus, $pakaiSurvei),
        );
    }

    /**
     * Hitung rute tanpa cache.
     *
     * Bila ada gang survei, rute dihitung di jaringan lengkap dan di jaringan OSM saja, lalu dipilih
     * yang lebih pendek. Titik kos atau kampus menempel ke ruas terdekat, dan gang yang lewat dekat
     * sebuah titik bisa membuat titik itu menempel ke gang, padahal lewat jalan biasa lebih pendek
     * beberapa meter. Dengan cara ini gang survei tidak pernah membuat rute lebih panjang.
     */
    public function hitung(Koordinat $dari, Koordinat $ke, bool $pakaiSurvei = true): array
    {
        $osm = $this->hitungDi($dari, $ke, "r.utama AND r.sumber = 'osm'");
        if (! $pakaiSurvei || ! DB::table('ruas_jalan')->where('sumber', 'survei')->exists()) {
            return $osm;
        }

        $lengkap = $this->hitungDi($dari, $ke, 'r.utama AND NOT r.terpecah');
        if (! $osm['tersedia']) {
            return $lengkap;
        }

        return $lengkap['tersedia'] && $lengkap['panjang_m'] < $osm['panjang_m'] ? $lengkap : $osm;
    }

    private function hitungDi(Koordinat $dari, Koordinat $ke, string $syaratRuas): array
    {
        $this->syaratRuas = $syaratRuas;

        $a = $this->tempel($dari);
        $b = $this->tempel($ke);
        if (! $a || ! $b) {
            return ['tersedia' => false, 'alasan' => 'Jaringan jalan belum diimpor. Jalankan php artisan jalan:import-osm.'];
        }

        if ($a->id === $b->id) {
            [$jalur, $panjangJalan] = $this->ruasSama($a, $b);
        } else {
            $hasil = $this->dijkstra($a, $b, true) ?? $this->dijkstra($a, $b, false);
            if (! $hasil) {
                // Tidak mungkin terjadi bila kedua titik menempel ke komponen utama, tapi tetap dijaga.
                return ['tersedia' => false, 'alasan' => 'Rute tidak ditemukan di jaringan jalan.'];
            }
            [$jalur, $panjangJalan] = $hasil;
        }

        $koordinat = $this->rapikan(array_merge([[$dari->lng, $dari->lat]], $jalur, [[$ke->lng, $ke->lat]]));
        $panjang = $a->jarak_m + $panjangJalan + $b->jarak_m;
        $kecepatanMeterPerMenit = (float) config('kostin.routing.kecepatan_jalan_kmh') * 1000 / 60;
        $batasCatatan = (float) config('kostin.routing.peringatan_jarak_ke_jalan_m');

        return [
            'tersedia' => true,
            'panjang_m' => (int) round($panjang),
            'durasi_menit' => max(1, (int) ceil($panjang / $kecepatanMeterPerMenit)),
            'jarak_ke_jalan_m' => (int) round($a->jarak_m),
            'catatan' => $a->jarak_m > $batasCatatan
                ? sprintf('Titik kos berjarak sekitar %d m dari jalan yang terpetakan, bagian itu digambar garis lurus.', round($a->jarak_m))
                : null,
            'geometri' => ['type' => 'LineString', 'coordinates' => $koordinat],
        ];
    }

    /** Ruas jalan utama terdekat dari titik, beserta posisi proyeksi (0..1) dan jaraknya dalam meter. */
    private function tempel(Koordinat $titik): ?object
    {
        // KNN (<->) memakai derajat, jadi ambil beberapa kandidat lalu urutkan ulang dengan jarak meter.
        $r = DB::selectOne(
            'SELECT * FROM (
                SELECT r.id, r.source, r.target, r.panjang_m,
                       ST_LineLocatePoint(r.geom, t.p) AS f,
                       ST_Distance(r.geom::geography, t.p::geography) AS jarak_m
                FROM ruas_jalan r, (SELECT ST_SetSRID(ST_MakePoint(?, ?), 4326) AS p) t
                WHERE '.$this->syaratRuas.'
                ORDER BY r.geom <-> t.p
                LIMIT 8
             ) k ORDER BY jarak_m, id LIMIT 1',
            [$titik->lng, $titik->lat],
        );

        return $r ? (object) [
            'id' => (int) $r->id,
            'source' => (int) $r->source,
            'target' => (int) $r->target,
            'panjang_m' => (float) $r->panjang_m,
            'f' => (float) $r->f,
            'jarak_m' => (float) $r->jarak_m,
        ] : null;
    }

    /** Kos dan kampus menempel di ruas yang sama: cukup potong ruas itu. */
    private function ruasSama(object $a, object $b): array
    {
        $balik = $a->f > $b->f;
        $jalur = $this->potongan($a->id, min($a->f, $b->f), max($a->f, $b->f));

        return [$balik ? array_reverse($jalur) : $jalur, abs($a->f - $b->f) * $a->panjang_m];
    }

    /**
     * @return array{0: list<array{0: float, 1: float}>, 1: float}|null [koordinat jalur, panjang di jaringan]
     */
    private function dijkstra(object $a, object $b, bool $batasiArea): ?array
    {
        $sqlGraf = 'SELECT r.id, r.source, r.target, r.panjang_m AS cost, r.panjang_m AS reverse_cost FROM ruas_jalan r WHERE '.$this->syaratRuas;
        if ($batasiArea) {
            // Hanya muat ruas di sekitar kedua titik supaya cepat. Bila rute tidak ditemukan,
            // dihitung ulang dengan seluruh jaringan.
            $kotak = DB::selectOne(
                'SELECT ST_XMin(e) AS x1, ST_YMin(e) AS y1, ST_XMax(e) AS x2, ST_YMax(e) AS y2
                 FROM (SELECT ST_Extent(geom) AS e FROM ruas_jalan WHERE id IN (?, ?)) k',
                [$a->id, $b->id],
            );
            $margin = max(0.01, 0.5 * max($kotak->x2 - $kotak->x1, $kotak->y2 - $kotak->y1));
            $sqlGraf .= sprintf(
                ' AND r.geom && ST_MakeEnvelope(%.7F, %.7F, %.7F, %.7F, 4326)',
                $kotak->x1 - $margin, $kotak->y1 - $margin, $kotak->x2 + $margin, $kotak->y2 + $margin,
            );
        }

        $awal = array_values(array_unique([(int) $a->source, (int) $a->target]));
        $akhir = array_values(array_unique([(int) $b->source, (int) $b->target]));

        $baris = DB::select(
            'SELECT d.start_vid, d.end_vid, d.path_seq, d.node, d.edge, d.agg_cost,
                    CASE WHEN d.edge < 0 THEN NULL
                         WHEN r.source = d.node THEN ST_AsGeoJSON(r.geom, 7)
                         ELSE ST_AsGeoJSON(ST_Reverse(r.geom), 7) END AS gj
             FROM pgr_dijkstra(?, ?::bigint[], ?::bigint[], false) d
             LEFT JOIN ruas_jalan r ON r.id = d.edge
             ORDER BY d.start_vid, d.end_vid, d.path_seq',
            [$sqlGraf, '{'.implode(',', $awal).'}', '{'.implode(',', $akhir).'}'],
        );

        // Biaya jaringan per pasangan simpul (baris terakhir tiap jalur, edge = -1).
        $biaya = [];
        foreach ($baris as $r) {
            if ((int) $r->edge < 0) {
                $biaya[$r->start_vid.'-'.$r->end_vid] = (float) $r->agg_cost;
            }
        }

        $terbaik = null;
        foreach ($awal as $s) {
            foreach ($akhir as $e) {
                $jaringan = $s === $e ? 0.0 : ($biaya[$s.'-'.$e] ?? null);
                if ($jaringan === null) {
                    continue;
                }
                $total = $this->sisaRuas($a, $s) + $jaringan + $this->sisaRuas($b, $e);
                if ($terbaik === null || $total < $terbaik['total']) {
                    $terbaik = ['s' => $s, 'e' => $e, 'total' => $total];
                }
            }
        }

        if ($terbaik === null) {
            return null;
        }

        ['s' => $s, 'e' => $e] = $terbaik;

        // Potongan ruas kos: dari titik tempel menuju simpul awal.
        $jalur = (int) $a->source === $s
            ? array_reverse($this->potongan($a->id, 0, $a->f))
            : $this->potongan($a->id, $a->f, 1);

        foreach ($baris as $r) {
            if ((int) $r->start_vid === $s && (int) $r->end_vid === $e && $r->gj !== null) {
                $jalur = array_merge($jalur, json_decode($r->gj, true)['coordinates']);
            }
        }

        // Potongan ruas kampus: dari simpul akhir menuju titik tempel.
        $jalur = array_merge($jalur, (int) $b->source === $e
            ? $this->potongan($b->id, 0, $b->f)
            : array_reverse($this->potongan($b->id, $b->f, 1)));

        return [$jalur, $terbaik['total']];
    }

    /** Panjang dari titik tempel di ruas ke salah satu ujungnya. */
    private function sisaRuas(object $tempel, int $simpul): float
    {
        return (int) $tempel->source === $simpul
            ? $tempel->f * $tempel->panjang_m
            : (1 - $tempel->f) * $tempel->panjang_m;
    }

    /** Koordinat potongan ruas antara posisi f1 dan f2 (0..1), searah geometri ruas. */
    private function potongan(int $idRuas, float $f1, float $f2): array
    {
        $gj = DB::scalar('SELECT ST_AsGeoJSON(ST_LineSubstring(geom, ?, ?), 7) FROM ruas_jalan WHERE id = ?', [$f1, $f2, $idRuas]);
        $geo = json_decode((string) $gj, true);

        return match ($geo['type'] ?? null) {
            'LineString' => $geo['coordinates'],
            'Point' => [$geo['coordinates']],
            default => [],
        };
    }

    /** Buang titik berurutan yang sama persis. */
    private function rapikan(array $koordinat): array
    {
        $hasil = [];
        foreach ($koordinat as $k) {
            $k = [round((float) $k[0], 7), round((float) $k[1], 7)];
            if ($hasil === [] || end($hasil) !== $k) {
                $hasil[] = $k;
            }
        }

        return $hasil;
    }
}