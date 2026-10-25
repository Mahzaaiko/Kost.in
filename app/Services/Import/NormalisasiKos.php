<?php

namespace App\Services\Import;

use App\Enums\TipeKos;
use App\Support\Format;
use App\Support\Koordinat;
use App\Support\PlusCode;

/**
 * Aturan pembakuan data dari spreadsheet "Pendataan Kos" (tab Salinan + Manual).
 * Semua fungsi murni (tanpa database) supaya mudah diuji satu per satu.
 */
class NormalisasiKos
{
    /** Harga satu angka di atas batas ini dianggap harga per tahun. */
    public const BATAS_HARGA_TAHUNAN = 3_000_000;

    /**
     * Pemetaan variasi penulisan fasilitas di spreadsheet ke nama master.
     * Kunci sudah dalam bentuk ternormalisasi (huruf kecil, tanpa tanda baca di ujung).
     *
     * @var array<string, list<string>>
     */
    public const PETA_FASILITAS = [
        'kamar mandi dalam' => ['Kamar Mandi Dalam'],
        'k mandi dalam' => ['Kamar Mandi Dalam'],
        'km dalam' => ['Kamar Mandi Dalam'],
        'kamar mandi dalem' => ['Kamar Mandi Dalam'],
        'kamar mandi luar' => ['Kamar Mandi Luar'],
        'k mandi luar' => ['Kamar Mandi Luar'],
        'kamar mandi luar 3' => ['Kamar Mandi Luar'],
        'kamar mandi dalam dan luar' => ['Kamar Mandi Dalam', 'Kamar Mandi Luar'],
        'water heater' => ['Water Heater'],
        'waterheater' => ['Water Heater'],
        'kasur' => ['Kasur'],
        'lemari' => ['Lemari'],
        'lemari storage' => ['Lemari'],
        'lemari 2' => ['Lemari'],
        'meja' => ['Meja'],
        'kursi' => ['Kursi'],
        'ac' => ['AC'],
        'kipas' => ['Kipas Angin'],
        'kipas angin' => ['Kipas Angin'],
        'wifi' => ['WiFi'],
        'free wifi' => ['WiFi'],
        'dapur' => ['Dapur'],
        'dapur bersama' => ['Dapur'],
        'dapur pribadi' => ['Dapur'],
        'dapur dan alat alat dapur' => ['Dapur'],
        'dapurruang tamu' => ['Dapur', 'Ruang Tamu'],
        'kulkas' => ['Kulkas'],
        'kulkas bersama' => ['Kulkas'],
        'kulkas gede' => ['Kulkas'],
        'dispenser' => ['Dispenser'],
        'mesin cuci' => ['Mesin Cuci'],
        'laundry' => ['Laundry'],
        'laundry room' => ['Laundry'],
        'tempat jemur' => ['Tempat Jemur'],
        'jemuran' => ['Tempat Jemur'],
        'ruang tv' => ['Ruang TV'],
        'tv' => ['Ruang TV'],
        'ruang tamu' => ['Ruang Tamu'],
        'parkir motor' => ['Parkir Motor'],
        'parkir motor sepeda' => ['Parkir Motor'],
        'parkir mobil' => ['Parkir Mobil'],
        'cctv' => ['CCTV'],
        'mushola' => ['Mushola'],
        'listrik' => ['Listrik Termasuk'],
        'include listrik' => ['Listrik Termasuk'],
        'air' => ['Air Termasuk'],
    ];

    public static function nama(?string $nilai): ?string
    {
        $nama = trim(preg_replace('/\s+/', ' ', (string) $nilai));
        $nama = trim($nama, " /\t");

        return $nama === '' ? null : mb_substr($nama, 0, 150);
    }

    /**
     * Nomor telepon dibakukan ke format 08xx / 02xx tanpa spasi.
     * Angka yang "hilang nol"-nya karena disimpan sebagai angka di spreadsheet dipulihkan.
     */
    public static function telepon(?string $nilai): ?string
    {
        $teks = trim((string) $nilai);
        if ($teks === '') {
            return null;
        }

        $teks = preg_replace('/\.0+$/', '', $teks);
        $angka = preg_replace('/\D/', '', $teks);

        if (str_starts_with($angka, '62')) {
            $angka = '0'.substr($angka, 2);
        } elseif (! str_starts_with($angka, '0')) {
            $angka = '0'.$angka;
        }

        $panjang = strlen($angka);

        return ($panjang >= 9 && $panjang <= 14) ? $angka : null;
    }

    public static function tipe(?string $nilai): ?TipeKos
    {
        $teks = mb_strtolower(trim((string) $nilai));

        return match (true) {
            $teks === '' => null,
            str_contains($teks, 'wanita') || str_contains($teks, 'putri') || str_contains($teks, 'perempuan') => TipeKos::Putri,
            str_contains($teks, 'campur') => TipeKos::Campur,
            str_contains($teks, 'laki') || str_contains($teks, 'putra') || str_contains($teks, 'pria') => TipeKos::Putra,
            default => null,
        };
    }

    /**
     * Periode minimal dalam bulan. Kalau tertulis beberapa ("Per 3 Bulan, 6 bulan, Tahun"),
     * yang diambil yang paling pendek karena itu syarat minimalnya.
     */
    public static function periodeBulan(?string $nilai): ?int
    {
        $teks = mb_strtolower(trim((string) $nilai));
        if ($teks === '') {
            return null;
        }

        $kandidat = [];
        foreach (preg_split('/[,;\/]/', $teks) as $bagian) {
            $bagian = trim($bagian);
            if (preg_match('/(\d+)\s*bulan/', $bagian, $m)) {
                $kandidat[] = (int) $m[1];
            } elseif (str_contains($bagian, 'tahun')) {
                $kandidat[] = 12;
            } elseif (str_contains($bagian, 'bulan')) {
                $kandidat[] = 1;
            }
        }

        $kandidat = array_values(array_filter($kandidat, fn ($b) => in_array($b, [1, 3, 6, 12], true)));

        return $kandidat === [] ? null : min($kandidat);
    }

    /**
     * Harga per bulan dan catatan asal-usulnya.
     *
     * @return array{harga: int|null, catatan: list<string>}
     */
    public static function harga(?string $nilai, ?int $periodeBulan): array
    {
        $teks = trim((string) $nilai);
        if ($teks === '') {
            return ['harga' => null, 'catatan' => []];
        }

        // "1250000, 1200000" atau "950000, 1800000,2000000": beberapa pilihan harga.
        $angka = [];
        foreach (preg_split('/[,;\/]|\s-\s/', $teks) as $bagian) {
            $bersih = preg_replace('/\.\d{1,2}$/', '', trim($bagian)); // 1800000.2 -> 1800000
            $bersih = preg_replace('/\D/', '', $bersih);
            if ($bersih !== '') {
                $angka[] = (int) $bersih;
            }
        }

        if ($angka === []) {
            return ['harga' => null, 'catatan' => ["Harga tidak terbaca: \"{$teks}\""]];
        }

        $catatan = [];
        $terendah = min($angka);
        if (count($angka) > 1) {
            $catatan[] = 'Pilihan harga di spreadsheet: '.implode(', ', array_map([Format::class, 'rupiah'], $angka)).'. Dipakai yang termurah.';
        }

        $harga = $terendah;
        if ($terendah >= self::BATAS_HARGA_TAHUNAN) {
            $harga = (int) round($terendah / 12 / 1000) * 1000;
            $catatan[] = 'Harga asal '.Format::rupiah($terendah).' dianggap per tahun, dibagi 12'
                .($periodeBulan === 12 ? '.' : ' (periode di spreadsheet bukan tahunan, mohon dicek).');
        }

        if ($harga < 100_000 || $harga > 20_000_000) {
            return ['harga' => null, 'catatan' => [...$catatan, 'Harga '.Format::rupiah($harga).' di luar batas wajar, dikosongkan.']];
        }

        return ['harga' => $harga, 'catatan' => $catatan];
    }

    /**
     * @return array{fasilitas: list<string>, lainnya: list<string>} nama master yang dikenali, dan sisanya
     */
    public static function fasilitas(?string $nilai): array
    {
        $dikenal = [];
        $lainnya = [];

        foreach (explode(',', (string) $nilai) as $mentah) {
            $mentah = trim($mentah);
            if ($mentah === '') {
                continue;
            }

            // Isi kurung kadang berisi fasilitas lain, mis. "Kamar Mandi Dalam(water heater)".
            $dalamKurung = preg_match('/\(([^)]*)\)/', $mentah, $m) ? self::kunci($m[1]) : null;
            $kunci = self::kunci(preg_replace('/\([^)]*\)/', '', $mentah));

            if (isset(self::PETA_FASILITAS[$kunci])) {
                array_push($dikenal, ...self::PETA_FASILITAS[$kunci]);
                if ($dalamKurung !== null && isset(self::PETA_FASILITAS[$dalamKurung])) {
                    array_push($dikenal, ...self::PETA_FASILITAS[$dalamKurung]);
                }
            } else {
                $lainnya[] = $mentah;
            }
        }

        return ['fasilitas' => array_values(array_unique($dikenal)), 'lainnya' => $lainnya];
    }

    /**
     * Alamat bersih + titik dari Plus Code kalau ada.
     *
     * @return array{alamat: string|null, lokasi: Koordinat|null, plus_code: string|null}
     */
    public static function alamat(?string $nilai, Koordinat $acuan): array
    {
        $alamat = trim(preg_replace('/\s+/', ' ', (string) $nilai));
        $alamat = preg_replace('/^alamat\s*:\s*/i', '', $alamat);

        $kode = PlusCode::cariDiTeks($alamat);
        $lokasi = $kode ? PlusCode::keKoordinat($kode, $acuan) : null;

        if ($kode) {
            // Plus Code bukan bagian alamat yang dibaca manusia.
            $alamat = trim(preg_replace('/^'.preg_quote($kode, '/').'\s*,?\s*/i', '', $alamat));
        }

        return [
            'alamat' => $alamat === '' ? null : mb_substr($alamat, 0, 500),
            'lokasi' => $lokasi,
            'plus_code' => $kode,
        ];
    }

    /** Kunci pencocokan: huruf kecil, tanpa tanda baca, spasi tunggal. */
    public static function kunci(string $teks): string
    {
        $teks = mb_strtolower($teks);
        $teks = preg_replace('/[^a-z0-9\s]/u', ' ', $teks);

        return trim(preg_replace('/\s+/', ' ', $teks));
    }
}
