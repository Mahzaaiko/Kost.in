<?php

namespace App\Services;

use App\Enums\TipeKos;
use App\Models\Kos;
use App\Services\Routing\JarakRuteKos;
use App\Support\Koordinat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Pencarian kos untuk halaman pengguna: hanya kos Aktif yang punya titik lokasi.
 *
 * Jarak acuan (filter jarak, urut jarak, kriteria jarak SAW) adalah jarak rute jalan kaki
 * di jaringan jalan (kolom jarak_rute_m). Jarak garis lurus (jarak_m) tetap dikirim sebagai
 * pembanding dan dipakai sebagai cadangan bila rute kos tidak tersedia.
 */
class PencarianKosService
{
    public const URUTAN = ['rekomendasi', 'jarak', 'murah', 'mahal'];

    public function __construct(
        private readonly RekomendasiService $rekomendasi,
        private readonly JarakRuteKos $jarakRute,
    ) {}

    /**
     * Query dasar: kos aktif + kolom jarak_m (meter, garis lurus ke kampus, dihitung PostGIS).
     */
    public function queryDasar(): Builder
    {
        return Kos::query()
            ->aktif()
            ->whereNotNull('lokasi')
            ->select('kos.*')
            ->selectRaw('ST_Distance(lokasi, ST_GeogFromText(?)) AS jarak_m', [Koordinat::kampus()->toEwkt()]);
    }

    /**
     * @param  array{harga_max?: int|null, jarak_max_km?: float|null, tipe?: string|null, fasilitas?: list<int>, q?: string|null, urut?: string|null}  $filter
     * @return Collection<int, Kos> setiap kos punya atribut jarak_m, jarak_acuan_m, dan skor
     */
    public function cari(array $filter): Collection
    {
        // Biasanya tidak ada yang dihitung: nilai sudah diperbarui saat kos disimpan atau jaringan berubah.
        $this->jarakRute->perbaruiYangUsang();

        $query = $this->queryDasar()->with(['fasilitas', 'foto']);

        if (! empty($filter['harga_max'])) {
            $query->where('harga_per_bulan', '<=', (int) $filter['harga_max']);
        }

        if (! empty($filter['jarak_max_km'])) {
            // Jarak rute jalan kaki lewat jaringan jalan, sama dengan area slider di peta.
            // Kos yang rutenya tidak tersedia (misalnya jaringan belum diimpor) memakai garis lurus.
            $query->whereRaw(
                'COALESCE(jarak_rute_m, ST_Distance(lokasi, ST_GeogFromText(?))) <= ?',
                [Koordinat::kampus()->toEwkt(), (float) $filter['jarak_max_km'] * 1000],
            );
        }

        if (! empty($filter['tipe']) && ($tipe = TipeKos::tryFrom($filter['tipe']))) {
            $query->where('tipe', $tipe->value);
        }

        $fasilitas = array_values(array_unique(array_map('intval', $filter['fasilitas'] ?? [])));
        if ($fasilitas !== []) {
            // Kos harus punya SEMUA fasilitas yang dicentang.
            $query->whereHas('fasilitas', fn (Builder $q) => $q->whereIn('fasilitas.id', $fasilitas), '=', count($fasilitas));
        }

        if (! empty($filter['q'])) {
            $pola = '%'.addcslashes(trim($filter['q']), '%_\\').'%';
            $query->where(fn (Builder $q) => $q->where('nama', 'ILIKE', $pola)->orWhere('alamat', 'ILIKE', $pola));
        }

        $hasil = $query->get();
        $hasil->each(fn (Kos $k) => $k->setAttribute('jarak_acuan_m', $k->jarak_rute_m ?? (float) $k->jarak_m));
        $this->isiSkor($hasil);

        return $this->urutkan($hasil, $filter['urut'] ?? 'rekomendasi');
    }

    /** Tambahkan atribut skor (SAW) ke setiap kos, relatif terhadap hasil saat ini. */
    public function isiSkor(Collection $daftar): void
    {
        $skor = $this->rekomendasi->hitung(
            $daftar->mapWithKeys(fn (Kos $k) => [$k->id => [
                'jarak' => (float) ($k->jarak_acuan_m ?? $k->jarak_rute_m ?? $k->jarak_m),
                'harga' => (int) $k->harga_per_bulan,
                'fasilitas' => $k->fasilitas->count(),
            ]])->all(),
        );

        $daftar->each(fn (Kos $k) => $k->setAttribute('skor', $skor[$k->id] ?? null));
    }

    private function urutkan(Collection $daftar, string $urut): Collection
    {
        $urutan = match ($urut) {
            'jarak' => [['jarak_acuan_m', 'asc']],
            'murah' => [['harga_per_bulan', 'asc'], ['jarak_acuan_m', 'asc']],
            'mahal' => [['harga_per_bulan', 'desc'], ['jarak_acuan_m', 'asc']],
            default => [['skor', 'desc'], ['jarak_acuan_m', 'asc']],
        };

        return $daftar->sortBy($urutan)->values();
    }
}
