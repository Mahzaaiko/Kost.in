<?php

namespace App\Services\Routing;

use App\Models\RekamanJalan;
use App\Support\Koordinat;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Menyambungkan gang hasil survei ke jaringan jalan (Fase Locality).
 *
 * Alur tambah rekaman:
 * 1. Titik GPS dirapikan (ST_SimplifyPreserveTopology di proyeksi UTM 48S, toleransi 3 m).
 * 2. Setiap ujung gang dicari ruas jalan terdekat dalam toleransi (15 m): ruas OSM, gang survei
 *    yang diunggah lebih dulu, atau potongan sambungannya. Ujung yang tidak dekat jalan mana pun
 *    dibiarkan sebagai gang buntu, tapi minimal satu ujung harus tersambung.
 * 3. Bila ujung menempel di tengah ruas, dibuat dua ruas 'sambungan' (potongan ruas itu dari kedua
 *    ujungnya sampai titik tempel). Ruas aslinya ditandai terpecah dan tidak dipakai rute lagi,
 *    tapi tetap disimpan untuk rute "OSM saja" di tabel validasi.
 * 4. Gang disimpan sebagai satu ruas 'survei', lalu komponen utama dihitung ulang dan versi
 *    jaringan diganti supaya cache rute lama tidak dipakai.
 *
 * Simpul baru memakai id negatif (-(id_rekaman * 10 + 1) dan -(id_rekaman * 10 + 2)),
 * jadi tidak bentrok dengan id node OSM yang selalu positif.
 */
class JaringanSurvei
{
    /** Ujung gang yang jaraknya ke ujung ruas di bawah ini langsung memakai simpul ruas itu. */
    private const BATAS_SIMPUL_M = 2.0;

    /** Panjang minimal gang setelah dirapikan. */
    private const PANJANG_MIN_M = 5.0;

    public function __construct(private ImportJaringanJalan $importOsm) {}

    /**
     * @param  list<array{0: float, 1: float}>  $titik  [lng, lat] hasil BacaRekamanGps
     *
     * @throws RuntimeException bila rekaman tidak bisa dipakai (pesan untuk admin)
     */
    public function tambah(array $titik, string $namaFile, ?string $nama = null, ?string $catatan = null, ?int $userId = null): RekamanJalan
    {
        $wkt = 'LINESTRING('.implode(',', array_map(fn ($p) => sprintf('%.7F %.7F', $p[0], $p[1]), $titik)).')';
        $rapi = DB::selectOne(
            'SELECT ST_AsText(g) AS wkt, ST_NPoints(g) AS n, ST_Length(g::geography) AS panjang
             FROM (SELECT ST_Transform(ST_SimplifyPreserveTopology(ST_Transform(ST_GeomFromText(?, 4326), 32748), ?), 4326) AS g) t',
            [$wkt, (float) config('kostin.routing.penyederhanaan_survei_m')],
        );

        if ((float) $rapi->panjang < self::PANJANG_MIN_M) {
            throw new RuntimeException(sprintf('Rekaman terlalu pendek (%.1f m). Pastikan GPS merekam sepanjang gang.', $rapi->panjang));
        }

        $kampus = Koordinat::kampus();
        $radiusKm = (float) config('kostin.routing.radius_import_km');
        $diLuar = DB::scalar(
            'SELECT NOT ST_DWithin(ST_StartPoint(g)::geography, k::geography, ?) OR NOT ST_DWithin(ST_EndPoint(g)::geography, k::geography, ?)
             FROM (SELECT ST_GeomFromText(?, 4326) AS g, ST_SetSRID(ST_MakePoint(?, ?), 4326) AS k) t',
            [$radiusKm * 1000, $radiusKm * 1000, $rapi->wkt, $kampus->lng, $kampus->lat],
        );
        if ($diLuar) {
            throw new RuntimeException(sprintf('Rekaman berada lebih dari %s km dari kampus, di luar area jaringan jalan. Periksa lagi filenya.', $radiusKm));
        }

        $awal = $this->cariTempel($rapi->wkt, true);
        $akhir = $this->cariTempel($rapi->wkt, false);
        if (! $awal && ! $akhir) {
            throw new RuntimeException(sprintf(
                'Kedua ujung rekaman lebih dari %d m dari jalan yang sudah ada, jadi gang ini tidak bisa disambungkan. Rekam mulai dari jalan yang sudah ada di peta sampai ke jalan lain atau ujung gang.',
                (int) config('kostin.routing.toleransi_snapping_m'),
            ));
        }
        if ($awal && $akhir && $awal->simpul !== null && $awal->simpul === $akhir->simpul) {
            throw new RuntimeException('Kedua ujung rekaman menempel di titik jalan yang sama, jadi gang ini tidak memperpendek rute mana pun.');
        }

        return DB::transaction(function () use ($rapi, $namaFile, $nama, $catatan, $userId) {
            $id = DB::table('rekaman_jalan')->insertGetId([
                'nama' => $nama ?: null,
                'catatan' => $catatan ?: null,
                'nama_file' => mb_substr($namaFile, 0, 255),
                'jumlah_titik' => (int) $rapi->n,
                'panjang_m' => round((float) $rapi->panjang, 1),
                'ujung_tersambung' => 0,
                'diunggah_oleh' => $userId,
                'geom' => DB::raw(sprintf("ST_GeomFromText('%s', 4326)", $rapi->wkt)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $rekaman = RekamanJalan::findOrFail($id);

            $this->sambungkan($rekaman);
            $this->selesai();

            return $rekaman->refresh();
        });
    }

    /** Hapus rekaman beserta ruasnya. Gang lain yang menempel ke gang ini disambungkan ulang. */
    public function hapus(RekamanJalan $rekaman): void
    {
        DB::transaction(function () use ($rekaman) {
            $rekaman->delete();
            $this->sambungkanUlangSemua();
            $this->selesai();
        });
    }

    /**
     * Hapus semua ruas survei/sambungan lalu sambungkan ulang setiap rekaman berurutan dari yang terlama.
     * Dipanggil setelah import ulang OSM (ruas OSM bisa berubah) dan setelah rekaman dihapus.
     * Tidak menghitung komponen utama, pemanggil yang melakukannya.
     */
    public function sambungkanUlangSemua(): void
    {
        DB::delete("DELETE FROM ruas_jalan WHERE sumber IN ('survei', 'sambungan')");
        DB::update('UPDATE ruas_jalan SET terpecah = false WHERE terpecah');
        foreach (RekamanJalan::query()->orderBy('id')->get() as $rekaman) {
            $this->sambungkan($rekaman);
        }
    }

    /** Buat ruas gang dan sambungannya untuk satu rekaman. */
    private function sambungkan(RekamanJalan $rekaman): void
    {
        $wkt = DB::scalar('SELECT ST_AsText(geom) FROM rekaman_jalan WHERE id = ?', [$rekaman->id]);

        // Ujung kedua dicari setelah sambungan ujung pertama dibuat, karena keduanya bisa menempel di ruas yang sama.
        $ujung = [];
        $simpul = [];
        foreach ([1 => true, 2 => false] as $ke => $awal) {
            $t = $this->cariTempel($wkt, $awal);
            $ujung[$ke] = $t;
            $simpul[$ke] = $t?->simpul ?? -($rekaman->id * 10 + $ke);
            if ($t && $t->simpul === null) {
                $this->buatSambungan($t, $simpul[$ke], $rekaman->id);
            }
        }

        $tersambung = count(array_filter($ujung));
        $rekaman->forceFill(['ujung_tersambung' => $tersambung])->saveQuietly();

        if ($tersambung === 0 || $simpul[1] === $simpul[2]) {
            // Jalan di sekitarnya berubah setelah import ulang. Rekaman disimpan, tapi tidak dipakai rute.
            return;
        }

        // Titik pertama/terakhir rekaman dipindah ke titik tempel supaya garis gang menyambung dengan jalan.
        // Dipindah, bukan ditambah, karena GPS sering mulai beberapa meter sebelum atau sesudah jalan.
        DB::insert(
            "INSERT INTO ruas_jalan (sumber, rekaman_id, nama, jenis, source, target, panjang_m, geom)
             SELECT 'survei', ?, ?, 'gang_survei', ?, ?, ST_Length(g::geography), g
             FROM (
                SELECT CASE WHEN ?::double precision IS NULL THEN g2 ELSE ST_SetPoint(g2, 0, ST_SetSRID(ST_MakePoint(?, ?), 4326)) END AS g
                FROM (
                    SELECT CASE WHEN ?::double precision IS NULL THEN g1 ELSE ST_SetPoint(g1, ST_NPoints(g1) - 1, ST_SetSRID(ST_MakePoint(?, ?), 4326)) END AS g2
                    FROM (SELECT ST_GeomFromText(?, 4326) AS g1) a
                ) b
             ) c",
            [
                $rekaman->id, $rekaman->namaTampil(), $simpul[1], $simpul[2],
                $ujung[1]?->lng, $ujung[1]?->lng, $ujung[1]?->lat,
                $ujung[2]?->lng, $ujung[2]?->lng, $ujung[2]?->lat,
                $wkt,
            ],
        );
    }

    /**
     * Ruas terdekat dari ujung gang dalam toleransi. Kandidat: semua ruas yang belum terpecah
     * (OSM, gang survei yang sudah ada, dan potongan sambungan). Ruas yang sudah terpecah diwakili
     * potongannya, jadi beberapa gang bisa menempel di ruas yang sama.
     *
     * simpul berisi id simpul bila titik tempel cukup dekat dengan ujung ruas, null bila di tengah ruas.
     */
    private function cariTempel(string $wktGang, bool $awal): ?object
    {
        $r = DB::selectOne(
            'SELECT * FROM (
                SELECT r.id, r.source, r.target, r.panjang_m,
                       ST_LineLocatePoint(r.geom, p.g) AS f,
                       ST_Distance(r.geom::geography, p.g::geography) AS jarak_m
                FROM ruas_jalan r,
                     (SELECT CASE WHEN ? THEN ST_StartPoint(x) ELSE ST_EndPoint(x) END AS g
                      FROM (SELECT ST_GeomFromText(?, 4326) AS x) s) p
                WHERE NOT r.terpecah
                ORDER BY r.geom <-> p.g
                LIMIT 8
             ) k WHERE jarak_m <= ? ORDER BY jarak_m, id LIMIT 1',
            [$awal, $wktGang, (float) config('kostin.routing.toleransi_snapping_m')],
        );

        if (! $r) {
            return null;
        }

        $f = (float) $r->f;
        $panjang = (float) $r->panjang_m;
        $simpul = match (true) {
            $f * $panjang <= self::BATAS_SIMPUL_M => (int) $r->source,
            (1 - $f) * $panjang <= self::BATAS_SIMPUL_M => (int) $r->target,
            default => null,
        };
        $fTempel = $simpul === null ? $f : ($simpul === (int) $r->source ? 0.0 : 1.0);
        $p = DB::selectOne(
            'SELECT ST_X(p) AS lng, ST_Y(p) AS lat FROM (SELECT ST_LineInterpolatePoint(geom, ?) AS p FROM ruas_jalan WHERE id = ?) t',
            [$fTempel, $r->id],
        );

        return (object) [
            'ruas' => (int) $r->id,
            'source' => (int) $r->source,
            'target' => (int) $r->target,
            'f' => $f,
            'simpul' => $simpul,
            'lng' => (float) $p->lng,
            'lat' => (float) $p->lat,
            'jarak_m' => (float) $r->jarak_m,
        ];
    }

    /** Dua potongan ruas dari kedua ujungnya ke titik tempel, sebagai jalan masuk ke simpul baru. */
    private function buatSambungan(object $tempel, int $simpulBaru, int $rekamanId): void
    {
        DB::update('UPDATE ruas_jalan SET terpecah = true WHERE id = ?', [$tempel->ruas]);

        foreach ([[0.0, $tempel->f, $tempel->source, $simpulBaru], [$tempel->f, 1.0, $simpulBaru, $tempel->target]] as [$f1, $f2, $dari, $ke]) {
            DB::insert(
                "INSERT INTO ruas_jalan (sumber, rekaman_id, nama, jenis, source, target, panjang_m, geom)
                 SELECT 'sambungan', ?, nama, jenis, ?, ?, ST_Length(g::geography), g
                 FROM (SELECT nama, jenis, ST_LineSubstring(geom, ?, ?) AS g FROM ruas_jalan WHERE id = ?) t",
                [$rekamanId, $dari, $ke, $f1, $f2, $tempel->ruas],
            );
        }
    }

    private function selesai(): void
    {
        $this->importOsm->tandaiKomponenUtama();
        $this->importOsm->gantiVersi();
    }
}