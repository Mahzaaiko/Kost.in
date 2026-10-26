<?php

namespace App\Services\Routing;

use App\Support\Koordinat;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use RuntimeException;

/**
 * Import jaringan jalan pejalan kaki dari OpenStreetMap (Overpass API) ke tabel ruas_jalan.
 *
 * Alur:
 * 1. Area lingkaran di sekitar kampus dipecah menjadi kotak (tile) kecil.
 * 2. Setiap tile diambil dari Overpass lalu langsung disimpan ke tabel sementara,
 *    jadi memori PHP tidak menampung seluruh area sekaligus. Tile yang berhasil diunduh
 *    juga disimpan di storage/app/private/osm-cache, sehingga kalau import terhenti,
 *    menjalankan ulang perintah hanya mengunduh tile yang belum ada.
 * 3. Jalan dipotong di setiap persimpangan (node OSM yang dipakai lebih dari satu kali)
 *    menjadi ruas-ruas dengan source/target = id node OSM. Ini dikerjakan di SQL lewat
 *    tabel sementara supaya waktunya sebanding dengan jumlah data (ratusan ribu jalan).
 * 4. Komponen jaringan terbesar ditandai utama (pgr_connectedComponents).
 */
class ImportJaringanJalan
{
    /** Nilai highway yang bukan jalan untuk pejalan kaki. */
    public const JENIS_DIKECUALIKAN = [
        'motorway', 'motorway_link', 'construction', 'proposed', 'abandoned', 'razed',
        'raceway', 'bus_guideway', 'platform', 'elevator', 'escape', 'bus_stop', 'rest_area', 'services',
    ];

    /** Kunci cache penanda versi jaringan. Berubah setiap import supaya cache rute lama tidak dipakai. */
    public const KUNCI_VERSI = 'rute:versi';

    /** Folder simpanan sementara tile Overpass di disk local (storage/app/private). */
    public const FOLDER_TILE = 'osm-cache';

    /** Jeda sebelum tile yang gagal dicoba ulang di akhir (detik). */
    public const JEDA_COBA_ULANG_DETIK = 30;

    /** Sudah pernah mengirim permintaan ke Overpass dalam proses ini (untuk jeda antar permintaan). */
    private bool $pernahUnduh = false;

    /** Pesan galat Overpass terakhir, untuk laporan. */
    private string $pesanTerakhir = '';

    /**
     * Daftar tile [selatan, barat, utara, timur] yang bersinggungan dengan lingkaran radius dari pusat.
     *
     * @return list<array{0: float, 1: float, 2: float, 3: float}>
     */
    public function tiles(Koordinat $pusat, float $radiusKm, float $ukuranKm): array
    {
        $kmPerDerajatLat = 111.32;
        $kmPerDerajatLng = 111.32 * cos(deg2rad($pusat->lat));

        $selatan = $pusat->lat - $radiusKm / $kmPerDerajatLat;
        $barat = $pusat->lng - $radiusKm / $kmPerDerajatLng;
        $jumlah = (int) ceil(2 * $radiusKm / $ukuranKm);
        $dLat = $ukuranKm / $kmPerDerajatLat;
        $dLng = $ukuranKm / $kmPerDerajatLng;

        $hasil = [];
        for ($i = 0; $i < $jumlah; $i++) {
            for ($j = 0; $j < $jumlah; $j++) {
                $s = $selatan + $i * $dLat;
                $w = $barat + $j * $dLng;
                $n = $s + $dLat;
                $e = $w + $dLng;

                // Titik tile yang paling dekat ke pusat; tile di luar lingkaran dilewati.
                $dy = (max($s, min($pusat->lat, $n)) - $pusat->lat) * $kmPerDerajatLat;
                $dx = (max($w, min($pusat->lng, $e)) - $pusat->lng) * $kmPerDerajatLng;
                if (sqrt($dx ** 2 + $dy ** 2) <= $radiusKm) {
                    $hasil[] = [round($s, 6), round($w, 6), round($n, 6), round($e, 6)];
                }
            }
        }

        return $hasil;
    }

    public function queryOverpass(array $bbox): string
    {
        $kecuali = implode('|', self::JENIS_DIKECUALIKAN);

        return sprintf(
            '[out:json][timeout:180];way["highway"]["highway"!~"^(%s)$"](%F,%F,%F,%F);out body geom;',
            $kecuali, ...$bbox,
        );
    }

    /** Apakah way OSM boleh dilalui pejalan kaki. */
    public function bolehDilalui(array $tags): bool
    {
        $highway = $tags['highway'] ?? null;
        if ($highway === null || in_array($highway, self::JENIS_DIKECUALIKAN, true)) {
            return false;
        }

        $foot = $tags['foot'] ?? null;
        if (in_array($foot, ['yes', 'designated', 'permissive'], true)) {
            return true;
        }

        return $foot !== 'no' && ($tags['access'] ?? null) !== 'no';
    }

    /**
     * Ambil satu tile: dari simpanan di disk bila ada, kalau tidak dari Overpass.
     *
     * @return array{0: list<array>, 1: bool} [elemen way mentah, true bila dari simpanan]
     */
    public function ambilTile(array $bbox, bool $pakaiSimpanan = true): array
    {
        $file = self::FOLDER_TILE.'/'.md5($this->queryOverpass($bbox)).'.json.gz';
        $disk = Storage::disk('local');

        if ($pakaiSimpanan && $disk->exists($file)) {
            $isi = json_decode((string) @gzdecode((string) $disk->get($file)), true);
            if (is_array($isi)) {
                return [$isi, true];
            }
        }

        if ($this->pernahUnduh) {
            Sleep::sleep(1); // jeda sopan antar permintaan ke server publik Overpass
        }
        $this->pernahUnduh = true;

        $ways = $this->unduhTile($bbox);
        $disk->put($file, gzencode(json_encode($ways), 6));

        return [$ways, false];
    }

    /** Hapus semua simpanan tile di disk. */
    public function hapusSimpananTile(): void
    {
        Storage::disk('local')->deleteDirectory(self::FOLDER_TILE);
    }

    /**
     * Unduh satu tile dari Overpass.
     *
     * @return list<array> elemen way mentah
     */
    private function unduhTile(array $bbox): array
    {
        try {
            $response = Http::asForm()
                ->withHeaders(['User-Agent' => 'Kost.in WebGIS (proyek kuliah Sekolah Vokasi IPB)'])
                ->timeout(200)
                ->retry(3, 15000, throw: false)
                ->post(config('kostin.routing.overpass_url'), ['data' => $this->queryOverpass($bbox)]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('koneksi gagal atau habis waktu');
        }

        if (! $response->successful()) {
            throw new RuntimeException('HTTP '.$response->status());
        }

        $elemen = $response->json('elements');
        if (! is_array($elemen)) {
            throw new RuntimeException('jawaban tanpa data jalan');
        }

        return array_values(array_filter($elemen, fn ($e) => ($e['type'] ?? null) === 'way'));
    }

    /**
     * Jalankan import.
     *
     * Tile yang gagal tidak langsung menghentikan import: tile lain tetap diambil, lalu tile yang
     * gagal dicoba sekali lagi di akhir. Tabel ruas_jalan hanya diganti bila semua tile lengkap.
     *
     * @param  (callable(int, int, string): void)|null  $progres  dipanggil per tile: (ke, total, keterangan)
     * @param  (callable(string): void)|null  $tahap  dipanggil saat masuk tahap pengolahan di database
     * @return array{tile: int, tile_simpanan: int, way: int, ruas: int, total_km: float, komponen: int, ruas_utama: int, km_utama: float}
     */
    public function jalankan(float $radiusKm, bool $dryRun = false, ?callable $progres = null, bool $pakaiSimpanan = true, ?callable $tahap = null): array
    {
        $tahap ??= fn (string $pesan) => null;

        $tiles = $this->tiles(Koordinat::kampus(), $radiusKm, (float) config('kostin.routing.ukuran_tile_km'));
        $total = count($tiles);

        $this->siapkanTabelSementara();

        try {
            $gagal = [];
            $dariSimpanan = 0;

            foreach ($tiles as $i => $bbox) {
                $keterangan = $this->prosesTile($bbox, $pakaiSimpanan, $dariSimpanan, $gagal);
                if ($progres) {
                    $progres($i + 1, $total, $keterangan);
                }
            }

            if ($gagal !== []) {
                // Server Overpass sering sibuk sesaat. Beri jeda lalu coba sekali lagi.
                Sleep::sleep(self::JEDA_COBA_ULANG_DETIK);
                $masihGagal = [];
                foreach ($gagal as $bbox) {
                    $this->prosesTile($bbox, $pakaiSimpanan, $dariSimpanan, $masihGagal);
                }
                if ($masihGagal !== []) {
                    throw new RuntimeException(sprintf(
                        '%d dari %d area gagal diambil dari Overpass (%s). Area yang sudah berhasil tersimpan sementara, jalankan ulang perintah yang sama beberapa menit lagi untuk melanjutkan.',
                        count($masihGagal), $total, $this->pesanTerakhir,
                    ));
                }
            }

            $jumlahWay = (int) DB::scalar('SELECT count(*) FROM osm_way_mentah');
            $tahap('Memotong '.number_format($jumlahWay, 0, ',', '.').' jalan di setiap persimpangan...');
            $this->bangunRuasSementara();

            if ($dryRun) {
                $ruas = DB::selectOne('SELECT count(*) AS jumlah, coalesce(sum(ST_Length(geom::geography)), 0) / 1000 AS km FROM ruas_mentah');

                return [
                    'tile' => $total, 'tile_simpanan' => $dariSimpanan, 'way' => $jumlahWay, 'ruas' => (int) $ruas->jumlah,
                    'total_km' => round((float) $ruas->km, 1), 'komponen' => 0, 'ruas_utama' => 0, 'km_utama' => 0.0,
                ];
            }

            $komponen = DB::transaction(function () use ($tahap) {
                $tahap('Menyimpan ruas jalan ke database...');
                DB::delete("DELETE FROM ruas_jalan WHERE sumber = 'osm'");
                DB::insert(
                    'INSERT INTO ruas_jalan (sumber, osm_way_id, nama, jenis, source, target, panjang_m, geom) '
                    ."SELECT 'osm', osm_way_id, nama, jenis, source, target, ST_Length(geom::geography), geom FROM ruas_mentah"
                );

                $tahap('Mencari jaringan jalan utama (pgr_connectedComponents)...');

                return $this->tandaiKomponenUtama();
            });
        } finally {
            DB::statement('DROP TABLE IF EXISTS ruas_mentah, titik_mentah, osm_way_mentah');
        }

        Cache::forever(self::KUNCI_VERSI, now()->format('YmdHis').'-'.bin2hex(random_bytes(3)));

        // Import sudah tersimpan di database, simpanan tile tidak diperlukan lagi.
        $this->hapusSimpananTile();

        $ringkas = DB::selectOne("SELECT count(*) AS ruas, coalesce(sum(panjang_m), 0) / 1000 AS km,
            count(*) FILTER (WHERE utama) AS ruas_utama, coalesce(sum(panjang_m) FILTER (WHERE utama), 0) / 1000 AS km_utama
            FROM ruas_jalan WHERE sumber = 'osm'");

        return [
            'tile' => $total,
            'tile_simpanan' => $dariSimpanan,
            'way' => $jumlahWay,
            'ruas' => (int) $ringkas->ruas,
            'total_km' => round((float) $ringkas->km, 1),
            'komponen' => $komponen,
            'ruas_utama' => (int) $ringkas->ruas_utama,
            'km_utama' => round((float) $ringkas->km_utama, 1),
        ];
    }

    /** Ambil dan simpan satu tile. Tile yang gagal dicatat di $gagal. Mengembalikan keterangan untuk progres. */
    private function prosesTile(array $bbox, bool $pakaiSimpanan, int &$dariSimpanan, array &$gagal): string
    {
        try {
            [$ways, $simpanan] = $this->ambilTile($bbox, $pakaiSimpanan);
        } catch (RuntimeException $e) {
            $gagal[] = $bbox;
            $this->pesanTerakhir = $e->getMessage();

            return 'gagal, dicoba ulang di akhir';
        }

        if ($simpanan) {
            $dariSimpanan++;
        }
        $this->simpanSementara($ways);

        return count($ways).' jalan'.($simpanan ? ' (simpanan)' : '');
    }

    /**
     * Tandai ruas yang termasuk komponen terhubung terbesar sebagai utama.
     *
     * @return int jumlah komponen di seluruh jaringan
     */
    public function tandaiKomponenUtama(): int
    {
        DB::update('UPDATE ruas_jalan SET utama = false WHERE utama');

        // Komponen dihitung sekali lalu disimpan sementara (pgRouting memuat seluruh graf setiap dipanggil).
        DB::statement('DROP TABLE IF EXISTS komponen_mentah');
        DB::statement(
            'CREATE TEMPORARY TABLE komponen_mentah AS SELECT component, node FROM pgr_connectedComponents(?)',
            ['SELECT id, source, target, panjang_m AS cost, panjang_m AS reverse_cost FROM ruas_jalan'],
        );
        DB::statement('ANALYZE komponen_mentah');

        try {
            $info = DB::selectOne(
                'SELECT (SELECT count(DISTINCT component) FROM komponen_mentah) AS jumlah,
                        (SELECT component FROM komponen_mentah GROUP BY component ORDER BY count(*) DESC, component LIMIT 1) AS terbesar'
            );

            if (! $info || $info->terbesar === null) {
                return 0;
            }

            DB::update(
                'UPDATE ruas_jalan r SET utama = true FROM komponen_mentah c WHERE c.node = r.source AND c.component = ?',
                [$info->terbesar],
            );

            return (int) $info->jumlah;
        } finally {
            DB::statement('DROP TABLE IF EXISTS komponen_mentah');
        }
    }

    private function siapkanTabelSementara(): void
    {
        DB::statement('DROP TABLE IF EXISTS osm_way_mentah');
        DB::statement('CREATE TEMPORARY TABLE osm_way_mentah (
            id bigint PRIMARY KEY,
            nama text,
            jenis text NOT NULL,
            nodes bigint[] NOT NULL,
            geom geometry(LineString, 4326) NOT NULL
        )');
    }

    /** Simpan way yang lolos filter ke tabel sementara. Way yang muncul di beberapa tile hanya disimpan sekali. */
    private function simpanSementara(array $ways): void
    {
        $baris = [];
        foreach ($ways as $w) {
            $tags = $w['tags'] ?? [];
            $nodes = $w['nodes'] ?? [];
            $geometri = $w['geometry'] ?? [];

            if (! $this->bolehDilalui($tags) || count($nodes) < 2 || count($nodes) !== count($geometri)) {
                continue;
            }

            $titik = [];
            foreach ($geometri as $g) {
                if (! isset($g['lat'], $g['lon'])) {
                    continue 2;
                }
                $titik[] = sprintf('%.7F %.7F', $g['lon'], $g['lat']);
            }

            $baris[] = [
                (int) $w['id'],
                isset($tags['name']) ? mb_substr($tags['name'], 0, 255) : null,
                mb_substr($tags['highway'], 0, 40),
                '{'.implode(',', array_map('intval', $nodes)).'}',
                'LINESTRING('.implode(',', $titik).')',
            ];
        }

        foreach (array_chunk($baris, 300) as $potongan) {
            $placeholder = implode(',', array_fill(0, count($potongan), '(?, ?, ?, ?::bigint[], ST_GeomFromText(?, 4326))'));
            DB::insert(
                "INSERT INTO osm_way_mentah (id, nama, jenis, nodes, geom) VALUES {$placeholder} ON CONFLICT (id) DO NOTHING",
                array_merge(...$potongan),
            );
        }
    }

    /**
     * Potong way menjadi ruas di setiap persimpangan, hasilnya di tabel sementara ruas_mentah
     * (osm_way_id, nama, jenis, source, target, geom).
     *
     * Caranya: setiap titik jalan diurai sekali (ST_DumpPoints), titik yang menjadi ujung jalan
     * atau dipakai lebih dari satu kali ditandai sebagai titik potong, lalu setiap titik diberi
     * nomor ruas = jumlah titik potong sebelumnya. Titik potong di tengah ikut menjadi titik awal
     * ruas berikutnya. Semua langkah berupa hash/sort biasa, jadi waktunya sebanding dengan
     * jumlah titik.
     */
    private function bangunRuasSementara(): void
    {
        DB::statement('DROP TABLE IF EXISTS ruas_mentah, titik_mentah');

        DB::statement('CREATE TEMPORARY TABLE titik_mentah AS
            SELECT w.id, d.path[1] AS idx, w.nodes[d.path[1]] AS node, d.geom
            FROM osm_way_mentah w
            CROSS JOIN LATERAL ST_DumpPoints(w.geom) d');
        DB::statement('ANALYZE titik_mentah');

        DB::statement(<<<'SQL'
            CREATE TEMPORARY TABLE ruas_mentah AS
            WITH pakai AS (
                SELECT node, count(*) AS jumlah FROM titik_mentah GROUP BY node
            ),
            tandai AS (
                SELECT t.id, t.idx, t.node, t.geom,
                       (t.idx = 1 OR t.idx = max(t.idx) OVER (PARTITION BY t.id) OR p.jumlah > 1) AS potong,
                       t.idx = max(t.idx) OVER (PARTITION BY t.id) AS akhir
                FROM titik_mentah t
                JOIN pakai p ON p.node = t.node
            ),
            nomor AS (
                SELECT id, idx, node, geom, potong, akhir,
                       coalesce(sum(potong::int) OVER (PARTITION BY id ORDER BY idx ROWS BETWEEN UNBOUNDED PRECEDING AND 1 PRECEDING), 0) AS seg
                FROM tandai
            ),
            anggota AS (
                SELECT id, seg, idx, node, geom FROM nomor WHERE seg > 0
                UNION ALL
                SELECT id, seg + 1, idx, node, geom FROM nomor WHERE potong AND NOT akhir
            ),
            ruas AS (
                SELECT id, seg,
                       (array_agg(node ORDER BY idx))[1] AS source,
                       (array_agg(node ORDER BY idx DESC))[1] AS target,
                       ST_MakeLine(geom ORDER BY idx) AS geom
                FROM anggota
                GROUP BY id, seg
                HAVING count(*) >= 2
            )
            SELECT r.id AS osm_way_id, w.nama, w.jenis, r.source, r.target, r.geom
            FROM ruas r
            JOIN osm_way_mentah w ON w.id = r.id
            SQL);
    }
}