<?php

namespace App\Services\Routing;

use App\Support\Koordinat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Jangkauan jalan kaki dari kampus (isochrone) dengan pgRouting (pgr_drivingDistance).
 *
 * Cara kerja:
 * 1. Titik kampus ditempelkan ke ruas jalan terdekat, sama seperti RuteService.
 * 2. pgr_drivingDistance menghitung jarak jaringan dari kedua ujung ruas itu ke setiap simpul
 *    dalam batas jarak terjauh. Jarak tiap simpul = sisa ruas kampus + jarak jaringan.
 * 3. Untuk setiap pita waktu (misalnya 5/10/15/20 menit), setiap ruas diambil sepanjang bagian
 *    yang masih terjangkau. Ruas yang hanya terjangkau sebagian dipotong (ST_LineSubstring),
 *    jadi batas jangkauan bisa jatuh di tengah ruas.
 * 4. Potongan ruas dilebarkan (ST_Buffer) dan digabung (ST_Union) supaya menjadi area di kiri-kanan jalan, lalu lubang kecil
 *    di tengahnya (blok rumah yang dikelilingi jalan terjangkau) ditutup.
 *
 * Berbeda dengan buffer garis lurus, area ini mengikuti jalan: terpotong sungai, rel, tembok,
 * dan gang buntu, serta melebar bila ada gang hasil survei yang menjadi jalan pintas.
 */
class JangkauanJalanKaki
{
    /** Pilihan jaringan: lengkap (OSM + gang survei) atau OSM saja (untuk pembanding). */
    public const JARINGAN = [
        'lengkap' => 'r.utama AND NOT r.terpecah',
        'osm' => "r.utama_osm AND r.sumber = 'osm'",
    ];

    /** @return array{type: string, features: list<array>, tersedia: bool, jaringan: string, alasan?: string} */
    public function dariKampus(string $jaringan = 'lengkap'): array
    {
        $jaringan = array_key_exists($jaringan, self::JARINGAN) ? $jaringan : 'lengkap';
        $kampus = Koordinat::kampus();
        $menit = $this->pitaMenit();

        $kunci = sprintf(
            'jangkauan:%s:%s:%.7F,%.7F:%s',
            Cache::get(ImportJaringanJalan::KUNCI_VERSI, '0'),
            $jaringan,
            $kampus->lat, $kampus->lng,
            md5(json_encode([config('kostin.peta'), config('kostin.routing.kecepatan_jalan_kmh')])),
        );

        return Cache::remember(
            $kunci,
            now()->addDays((int) config('kostin.routing.cache_hari')),
            fn () => $this->hitung($kampus, $menit, $jaringan),
        );
    }

    /**
     * Hitung jangkauan tanpa cache.
     *
     * @param  list<int>  $menit
     */
    public function hitung(Koordinat $asal, array $menit, string $jaringan = 'lengkap'): array
    {
        $syarat = self::JARINGAN[$jaringan] ?? self::JARINGAN['lengkap'];
        $kosong = ['type' => 'FeatureCollection', 'features' => [], 'tersedia' => false, 'jaringan' => $jaringan];

        $tempel = $this->tempel($asal, $syarat);
        if (! $tempel) {
            return $kosong + ['alasan' => 'Jaringan jalan belum diimpor. Jalankan php artisan jalan:import-osm.'];
        }

        $kecepatan = (float) config('kostin.routing.kecepatan_jalan_kmh') * 1000 / 60;
        // Jarak dari titik kampus ke jalan sudah memakai sebagian waktu tempuh.
        $batas = array_map(fn (int $m) => max(0.0, $m * $kecepatan - $tempel->jarak_m), $menit);
        $maks = max($batas);
        if ($maks <= 0) {
            return $kosong + ['alasan' => 'Titik kampus terlalu jauh dari jalan yang terpetakan.'];
        }

        // Hanya muat ruas di sekitar kampus supaya cepat (jarak jaringan selalu >= garis lurus).
        $derajat = ($maks + 500) / 110_000;
        $sqlGraf = sprintf(
            'SELECT r.id, r.source, r.target, r.panjang_m AS cost, r.panjang_m AS reverse_cost
             FROM ruas_jalan r
             WHERE %s AND r.geom && ST_Expand(ST_SetSRID(ST_MakePoint(%.7F, %.7F), 4326), %.7F)',
            $syarat, $asal->lng, $asal->lat, $derajat,
        );

        $baris = DB::select(
            "WITH awal AS (
                SELECT * FROM (VALUES (?::bigint, ?::float8), (?::bigint, ?::float8)) a(simpul, jarak)
             ),
             simpul AS (
                SELECT d.node, MIN(a.jarak + d.agg_cost) AS jarak
                FROM pgr_drivingDistance(?, ARRAY[?, ?]::bigint[], ?, false) d
                JOIN awal a ON a.simpul = d.start_vid
                GROUP BY d.node
             ),
             pita AS (
                SELECT * FROM unnest(?::int[], ?::float8[]) p(menit, batas)
             ),
             ruas AS (
                SELECT r.geom, r.panjang_m, ds.jarak AS js, dt.jarak AS jt
                FROM ruas_jalan r
                LEFT JOIN simpul ds ON ds.node = r.source
                LEFT JOIN simpul dt ON dt.node = r.target
                WHERE {$syarat} AND (ds.node IS NOT NULL OR dt.node IS NOT NULL)
             ),
             porsi AS (
                -- Bagian ruas yang terjangkau dari ujung source (fs) dan dari ujung target (ft), 0..1.
                SELECT p.menit, x.geom,
                       CASE WHEN x.js < p.batas THEN CASE WHEN x.panjang_m > 0 THEN LEAST(1, (p.batas - x.js) / x.panjang_m) ELSE 1 END ELSE 0 END AS fs,
                       CASE WHEN x.jt < p.batas THEN CASE WHEN x.panjang_m > 0 THEN LEAST(1, (p.batas - x.jt) / x.panjang_m) ELSE 1 END ELSE 0 END AS ft
                FROM ruas x CROSS JOIN pita p
             ),
             garis AS (
                SELECT menit, ST_LineSubstring(geom, 0, CASE WHEN fs + ft >= 1 THEN 1 ELSE fs END) AS g FROM porsi WHERE fs > 0
                UNION ALL
                SELECT menit, ST_LineSubstring(geom, 1 - ft, 1) FROM porsi WHERE ft > 0 AND fs + ft < 1
                UNION ALL
                -- Ruas tempat kampus menempel: terjangkau ke dua arah dari titik tempel.
                SELECT p.menit, ST_LineSubstring(r.geom, GREATEST(0, ? - p.batas / r.panjang_m), LEAST(1, ? + p.batas / r.panjang_m))
                FROM ruas_jalan r CROSS JOIN pita p
                WHERE r.id = ? AND r.panjang_m > 0 AND p.batas > 0
             ),
             area AS (
                -- Setiap potongan dilebarkan dulu lalu digabung (cascaded union): jauh lebih cepat
                -- daripada melebarkan gabungan semua garis sekaligus di jaringan yang rapat.
                SELECT menit, ST_Union(ST_Buffer(ST_Transform(g, 32748), ?, 'quad_segs=3')) AS a
                FROM garis
                GROUP BY menit
             ),
             rapi AS (
                SELECT area.menit, ST_Collect(ST_MakePolygon(
                           ST_ExteriorRing(d.geom),
                           ARRAY(SELECT ST_InteriorRingN(d.geom, n)
                                 FROM generate_series(1, ST_NumInteriorRings(d.geom)) n
                                 WHERE ST_Area(ST_MakePolygon(ST_InteriorRingN(d.geom, n))) > ?)
                       )) AS a
                FROM area, LATERAL ST_Dump(area.a) d
                GROUP BY area.menit
             )
             SELECT menit, ST_Area(a) AS luas_m2,
                    ST_AsGeoJSON(ST_Transform(ST_SimplifyPreserveTopology(a, 2), 4326), 6) AS gj
             FROM rapi
             ORDER BY menit DESC",
            [
                $tempel->source, $tempel->f * $tempel->panjang_m, $tempel->target, (1 - $tempel->f) * $tempel->panjang_m,
                $sqlGraf, $tempel->source, $tempel->target, $maks,
                '{'.implode(',', $menit).'}', '{'.implode(',', $batas).'}',
                $tempel->f, $tempel->f, $tempel->id,
                (float) config('kostin.peta.jangkauan_lebar_m'),
                (float) config('kostin.peta.jangkauan_lubang_maks_m2'),
            ],
        );

        $fitur = array_map(fn ($r) => [
            'type' => 'Feature',
            'properties' => [
                'menit' => (int) $r->menit,
                'jarak_m' => (int) round($r->menit * $kecepatan),
                'luas_ha' => round($r->luas_m2 / 10_000, 1),
            ],
            'geometry' => json_decode($r->gj, true),
        ], $baris);

        return ['type' => 'FeatureCollection', 'features' => $fitur, 'tersedia' => $fitur !== [], 'jaringan' => $jaringan];
    }

    /** @return list<int> pita waktu dari konfigurasi, unik dan urut naik */
    private function pitaMenit(): array
    {
        $menit = array_values(array_unique(array_filter(
            array_map('intval', (array) config('kostin.peta.jangkauan_menit')),
            fn (int $m) => $m > 0,
        )));
        sort($menit);

        return $menit ?: [5, 10, 15];
    }

    /** Ruas terdekat dari titik, beserta posisi proyeksi (0..1) dan jaraknya dalam meter. */
    private function tempel(Koordinat $titik, string $syarat): ?object
    {
        $r = DB::selectOne(
            'SELECT * FROM (
                SELECT r.id, r.source, r.target, r.panjang_m,
                       ST_LineLocatePoint(r.geom, t.p) AS f,
                       ST_Distance(r.geom::geography, t.p::geography) AS jarak_m
                FROM ruas_jalan r, (SELECT ST_SetSRID(ST_MakePoint(?, ?), 4326) AS p) t
                WHERE '.$syarat.'
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
}