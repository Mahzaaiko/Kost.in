<?php

namespace App\Services\Routing;

use App\Models\Kos;
use Illuminate\Support\Facades\Cache;

/**
 * Jarak rute jalan kaki setiap kos ke kampus, disimpan di kolom kos.jarak_rute_m.
 *
 * Nilai ini dipakai pencarian (filter jarak, urut jarak) dan kriteria jarak SAW,
 * menggantikan jarak garis lurus. Sumbernya sama dengan tombol Lihat Rute (RuteService,
 * jaringan OSM + gang survei), jadi angka di daftar, rute, dan peta jangkauan selalu konsisten.
 *
 * Setiap nilai menyimpan versi jaringan saat dihitung (jarak_rute_versi). Nilai yang versinya
 * berbeda dengan versi jaringan sekarang dianggap usang dan dihitung ulang.
 */
class JarakRuteKos
{
    public function __construct(private RuteService $rute) {}

    public static function versiSekarang(): string
    {
        return (string) Cache::get(ImportJaringanJalan::KUNCI_VERSI, '0');
    }

    /** Kecepatan jalan kaki dalam meter per menit, sama dengan RuteService. */
    public static function meterPerMenit(): float
    {
        return (float) config('kostin.routing.kecepatan_jalan_kmh') * 1000 / 60;
    }

    public static function menit(?int $meter): ?int
    {
        return $meter === null ? null : max(1, (int) ceil($meter / self::meterPerMenit()));
    }

    /** Hitung dan simpan jarak rute satu kos. */
    public function perbarui(Kos $kos): ?int
    {
        $hasil = $kos->lokasi ? $this->rute->untukKos($kos) : ['tersedia' => false];
        $meter = $hasil['tersedia'] ? (int) $hasil['panjang_m'] : null;

        // saveQuietly: perubahan ini tidak boleh memicu event saving yang mengosongkan versi lagi.
        $kos->forceFill(['jarak_rute_m' => $meter, 'jarak_rute_versi' => self::versiSekarang()])->saveQuietly();

        return $meter;
    }

    /**
     * Hitung ulang semua kos yang nilainya usang (lokasi berubah atau jaringan jalan berubah).
     *
     * @return int jumlah kos yang dihitung ulang
     */
    public function perbaruiYangUsang(): int
    {
        $versi = self::versiSekarang();
        $jumlah = 0;

        Kos::query()
            ->where(fn ($q) => $q->whereNull('jarak_rute_versi')->orWhere('jarak_rute_versi', '!=', $versi))
            ->orderBy('id')
            ->each(function (Kos $kos) use (&$jumlah) {
                $this->perbarui($kos);
                $jumlah++;
            });

        return $jumlah;
    }
}
