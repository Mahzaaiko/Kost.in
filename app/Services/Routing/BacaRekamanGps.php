<?php

namespace App\Services\Routing;

use DOMDocument;
use DOMElement;
use RuntimeException;

/**
 * Membaca file rekaman gang menjadi daftar titik [lng, lat].
 *
 * Format yang diterima:
 * - GPX dari aplikasi GPS (GPS Logger, OsmAnd, Open GPX Tracker): titik <trkpt>, atau <rtept> bila tidak ada track.
 *   Semua segmen digabung berurutan.
 * - GeoJSON: LineString atau MultiLineString, boleh dibungkus Feature/FeatureCollection.
 *   Satu file hanya untuk satu gang.
 *
 * Pesan galat ditulis untuk admin, karena tampil langsung di halaman upload.
 */
class BacaRekamanGps
{
    /**
     * @return list<array{0: float, 1: float}>
     *
     * @throws RuntimeException
     */
    public function baca(string $isi, string $namaFile): array
    {
        $ekstensi = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
        $isi = ltrim($isi, "\xEF\xBB\xBF \t\r\n");

        $titik = match (true) {
            $ekstensi === 'gpx', str_starts_with($isi, '<') => $this->dariGpx($isi),
            default => $this->dariGeoJson($isi),
        };

        $titik = $this->buangDuplikat($titik);
        if (count($titik) < 2) {
            throw new RuntimeException('Rekaman hanya berisi kurang dari 2 titik berbeda. Pastikan GPS merekam selama berjalan menyusuri gang.');
        }

        return $titik;
    }

    /** @return list<array{0: float, 1: float}> */
    private function dariGpx(string $isi): array
    {
        $dom = new DOMDocument;
        $sebelumnya = libxml_use_internal_errors(true);
        // LIBXML_NONET: jangan pernah mengambil apa pun dari jaringan saat membaca file unggahan.
        $berhasil = $dom->loadXML($isi, LIBXML_NONET | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($sebelumnya);

        if (! $berhasil) {
            throw new RuntimeException('File GPX tidak bisa dibaca. Ekspor ulang rekaman dari aplikasi GPS sebagai GPX.');
        }

        $titik = [];
        foreach (['trkpt', 'rtept'] as $tag) {
            foreach ($dom->getElementsByTagNameNS('*', $tag) as $node) {
                if (! $node instanceof DOMElement) {
                    continue;
                }
                $p = $this->titikValid($node->getAttribute('lon'), $node->getAttribute('lat'));
                if ($p) {
                    $titik[] = $p;
                }
            }
            if ($titik !== []) {
                return $titik;
            }
        }

        throw new RuntimeException('File GPX tidak berisi jalur (track). Pastikan yang diekspor adalah rekaman perjalanan, bukan titik tanda (waypoint).');
    }

    /** @return list<array{0: float, 1: float}> */
    private function dariGeoJson(string $isi): array
    {
        $data = json_decode($isi, true);
        if (! is_array($data) || ! isset($data['type'])) {
            throw new RuntimeException('File bukan GPX atau GeoJSON yang valid.');
        }

        $garis = $this->kumpulkanGaris($data);
        if ($garis === []) {
            throw new RuntimeException('GeoJSON tidak berisi garis (LineString). Gambar gang sebagai garis, bukan titik atau area.');
        }
        if (count($garis) > 1) {
            throw new RuntimeException('GeoJSON berisi lebih dari satu garis. Satu file hanya untuk satu gang, pisahkan setiap gang ke file sendiri.');
        }

        $titik = [];
        foreach ($garis[0] as $k) {
            $p = is_array($k) && count($k) >= 2 ? $this->titikValid($k[0], $k[1]) : null;
            if ($p) {
                $titik[] = $p;
            }
        }

        return $titik;
    }

    /**
     * Kumpulkan garis dari objek GeoJSON. MultiLineString dihitung satu garis (bagian-bagiannya digabung).
     *
     * @return list<array<int, mixed>>
     */
    private function kumpulkanGaris(array $geo): array
    {
        return match ($geo['type'] ?? null) {
            'FeatureCollection' => array_merge(...array_map(
                fn ($f) => is_array($f) ? $this->kumpulkanGaris($f) : [],
                array_values((array) ($geo['features'] ?? [])),
            ) ?: [[]]),
            'Feature' => is_array($geo['geometry'] ?? null) ? $this->kumpulkanGaris($geo['geometry']) : [],
            'LineString' => [(array) ($geo['coordinates'] ?? [])],
            'MultiLineString' => [array_merge(...array_map(fn ($b) => (array) $b, array_values((array) ($geo['coordinates'] ?? []))) ?: [[]])],
            default => [],
        };
    }

    /** @return array{0: float, 1: float}|null */
    private function titikValid(mixed $lng, mixed $lat): ?array
    {
        if (! is_numeric($lng) || ! is_numeric($lat)) {
            return null;
        }
        $lng = (float) $lng;
        $lat = (float) $lat;

        return ($lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180) ? [$lng, $lat] : null;
    }

    /** Buang titik berurutan yang sama persis (GPS diam di tempat). */
    private function buangDuplikat(array $titik): array
    {
        $hasil = [];
        foreach ($titik as $p) {
            if ($hasil === [] || end($hasil) !== $p) {
                $hasil[] = $p;
            }
        }

        return $hasil;
    }
}