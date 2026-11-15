<?php

namespace App\Services\Routing;

use App\Support\Koordinat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Layer jaringan jalan di sekitar kampus untuk peta Jelajah, dibagi dua kategori:
 * jalan OSM dan gang hasil survei (Fase Locality).
 *
 * Ruas yang sudah terpecah diwakili potongan sambungannya, dan potongan itu ikut kategori ruas
 * asalnya (OSM atau gang survei), jadi setiap jalan hanya tergambar sekali. Ruas dikumpulkan per
 * kategori menjadi satu MultiLineString, ruas yang bersambung digabung (ST_LineMerge), dan koordinat
 * dibulatkan 5 desimal (±1 m) supaya ringan dikirim ke browser.
 */
class LayerJaringanJalan
{
    /** @return array{type: string, features: list<array>} */
    public function sekitarKampus(): array
    {
        $kampus = Koordinat::kampus();
        $radiusM = (float) config('kostin.peta.radius_jalan_km') * 1000;

        $kunci = sprintf(
            'layer-jalan:%s:%.7F,%.7F:%d',
            Cache::get(ImportJaringanJalan::KUNCI_VERSI, '0'),
            $kampus->lat, $kampus->lng, $radiusM,
        );

        return Cache::remember(
            $kunci,
            now()->addDays((int) config('kostin.routing.cache_hari')),
            fn () => $this->hitung($kampus, $radiusM),
        );
    }

    public function hitung(Koordinat $pusat, float $radiusM): array
    {
        $baris = DB::select(
            "SELECT CASE WHEN r.jenis = 'gang_survei' THEN 'survei' ELSE 'osm' END AS kategori,
                    COUNT(*) AS jumlah_ruas,
                    SUM(r.panjang_m) AS panjang_m,
                    ST_AsGeoJSON(ST_Multi(ST_LineMerge(ST_Collect(ST_SimplifyPreserveTopology(r.geom, 0.000005)))), 5) AS gj
             FROM ruas_jalan r, (SELECT ST_SetSRID(ST_MakePoint(?, ?), 4326) AS p) t
             WHERE NOT r.terpecah
               AND r.geom && ST_Expand(t.p, ?)
               AND ST_DWithin(r.geom::geography, t.p::geography, ?)
             GROUP BY 1
             ORDER BY 1",
            [$pusat->lng, $pusat->lat, ($radiusM + 100) / 110_000, $radiusM],
        );

        $label = ['osm' => 'Jalan OpenStreetMap', 'survei' => 'Gang hasil survei'];

        return [
            'type' => 'FeatureCollection',
            'features' => array_map(fn ($r) => [
                'type' => 'Feature',
                'properties' => [
                    'kategori' => $r->kategori,
                    'label' => $label[$r->kategori],
                    'jumlah_ruas' => (int) $r->jumlah_ruas,
                    'panjang_km' => round($r->panjang_m / 1000, 2),
                ],
                'geometry' => json_decode($r->gj, true),
            ], $baris),
        ];
    }
}