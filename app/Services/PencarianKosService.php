<?php

namespace App\Services;

use App\Enums\TipeKos;
use App\Models\Kos;
use App\Support\Koordinat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/** Pencarian kos untuk halaman pengguna: hanya kos Aktif yang punya titik lokasi. */
class PencarianKosService
{
    public const URUTAN = ['rekomendasi', 'jarak', 'murah', 'mahal'];

    public function __construct(private readonly RekomendasiService $rekomendasi) {}

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
     * @return Collection<int, Kos> setiap kos punya atribut jarak_m dan skor
     */
    public function cari(array $filter): Collection
    {
        $query = $this->queryDasar()->with(['fasilitas', 'foto']);

        if (! empty($filter['harga_max'])) {
            $query->where('harga_per_bulan', '<=', (int) $filter['harga_max']);
        }

        if (! empty($filter['jarak_max_km'])) {
            // Buffer: kos di dalam radius X meter dari kampus (garis lurus).
            $query->whereRaw(
                'ST_DWithin(lokasi, ST_GeogFromText(?), ?)',
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
        $this->isiSkor($hasil);

        return $this->urutkan($hasil, $filter['urut'] ?? 'rekomendasi');
    }

    /** Tambahkan atribut skor (SAW) ke setiap kos, relatif terhadap hasil saat ini. */
    public function isiSkor(Collection $daftar): void
    {
        $skor = $this->rekomendasi->hitung(
            $daftar->mapWithKeys(fn (Kos $k) => [$k->id => [
                'jarak' => (float) $k->jarak_m,
                'harga' => (int) $k->harga_per_bulan,
                'fasilitas' => $k->fasilitas->count(),
            ]])->all(),
        );

        $daftar->each(fn (Kos $k) => $k->setAttribute('skor', $skor[$k->id] ?? null));
    }

    private function urutkan(Collection $daftar, string $urut): Collection
    {
        $urutan = match ($urut) {
            'jarak' => [['jarak_m', 'asc']],
            'murah' => [['harga_per_bulan', 'asc'], ['jarak_m', 'asc']],
            'mahal' => [['harga_per_bulan', 'desc'], ['jarak_m', 'asc']],
            default => [['skor', 'desc'], ['jarak_m', 'asc']],
        };

        return $daftar->sortBy($urutan)->values();
    }
}