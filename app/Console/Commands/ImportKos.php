<?php

namespace App\Console\Commands;

use App\Enums\StatusKos;
use App\Models\Fasilitas;
use App\Models\Kos;
use App\Services\Import\NormalisasiKos;
use App\Support\Koordinat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Import data kos dari CSV hasil "File > Download > CSV" tab "Salinan + Manual".
 *
 * Semua kos masuk sebagai Draft. Kos yang namanya sudah ada di database dilewati,
 * jadi perintah ini aman dijalankan ulang tanpa menimpa hasil edit admin.
 */
class ImportKos extends Command
{
    protected $signature = 'kos:import
        {file : Path file CSV}
        {--dry-run : Tampilkan hasil tanpa menyimpan ke database}';

    protected $description = 'Import data kos dari CSV spreadsheet pendataan (semua masuk sebagai Draft)';

    /** Judul kolom di spreadsheet (awalan, huruf kecil) => nama field. */
    private const KOLOM = [
        'nama kos' => 'nama',
        'nomor telepon' => 'telepon',
        'jenis kos' => 'jenis',
        'periode minimal' => 'periode',
        'harga sewa' => 'harga',
        'fasilitas' => 'fasilitas',
        'alamat' => 'alamat',
        'foto' => 'foto',
    ];

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error("File tidak ditemukan atau tidak bisa dibaca: {$path}");

            return self::FAILURE;
        }

        try {
            $baris = $this->bacaCsv($path);
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $kampus = Koordinat::kampus();
        $grup = $this->kelompokkan($baris, $kampus);

        $idFasilitas = Fasilitas::pluck('id', 'nama');
        $namaAda = Kos::pluck('nama')->map(fn ($n) => mb_strtolower($n))->flip();

        $laporan = ['dibuat' => [], 'dilewati' => [], 'peringatan' => []];

        DB::beginTransaction();
        try {
            foreach ($grup as $data) {
                if (isset($namaAda[mb_strtolower($data['nama'])])) {
                    $laporan['dilewati'][] = "{$data['nama']} (sudah ada di database)";

                    continue;
                }

                $kos = Kos::create([
                    'nama' => $data['nama'],
                    'no_telepon' => $data['telepon'],
                    'alamat' => $data['alamat'],
                    'tipe' => $data['tipe'],
                    'periode_minimal_bulan' => $data['periode'],
                    'harga_per_bulan' => $data['harga'],
                    'lokasi' => $data['lokasi'],
                    'status' => StatusKos::Draft,
                    'catatan_import' => $data['catatan'] === [] ? null : implode("\n", $data['catatan']),
                ]);

                $ids = collect($data['fasilitas'])->map(fn ($nama) => $idFasilitas[$nama] ?? null)->filter()->values();
                $kos->fasilitas()->sync($ids);

                $laporan['dibuat'][] = $data;
                foreach ($data['catatan'] as $c) {
                    $laporan['peringatan'][] = "{$data['nama']}: {$c}";
                }
            }

            $this->option('dry-run') ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->tampilkanLaporan($laporan, count($baris));

        return self::SUCCESS;
    }

    /**
     * @return list<array<string, string>> baris data dengan kunci field
     */
    private function bacaCsv(string $path): array
    {
        $isi = file_get_contents($path);
        $isi = preg_replace('/^\xEF\xBB\xBF/', '', $isi); // buang BOM
        $barisPertama = strtok($isi, "\n");
        $pemisah = substr_count($barisPertama, ';') > substr_count($barisPertama, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $isi);
        rewind($stream);

        $header = fgetcsv($stream, null, $pemisah, '"', '');
        $peta = [];
        foreach ((array) $header as $i => $judul) {
            $judul = mb_strtolower(trim((string) $judul));
            foreach (self::KOLOM as $awalan => $field) {
                if (! isset($peta[$field]) && str_starts_with($judul, $awalan)) {
                    $peta[$field] = $i;
                }
            }
        }

        if (! isset($peta['nama'])) {
            fclose($stream);
            throw new \RuntimeException('Kolom "Nama Kos" tidak ditemukan di baris judul. Pastikan yang diekspor tab "Salinan + Manual".');
        }

        $hasil = [];
        while (($kolom = fgetcsv($stream, null, $pemisah, '"', '')) !== false) {
            $data = [];
            foreach ($peta as $field => $i) {
                $data[$field] = trim((string) ($kolom[$i] ?? ''));
            }
            if (implode('', $data) !== '') {
                $hasil[] = $data;
            }
        }
        fclose($stream);

        return $hasil;
    }

    /**
     * Normalisasi tiap baris lalu gabungkan baris bernama sama (mis. dua tipe kamar).
     *
     * @param  list<array<string, string>>  $baris
     * @return array<string, array<string, mixed>>
     */
    private function kelompokkan(array $baris, Koordinat $kampus): array
    {
        $grup = [];

        foreach ($baris as $b) {
            $nama = NormalisasiKos::nama($b['nama'] ?? null);
            if ($nama === null) {
                continue;
            }

            $periode = NormalisasiKos::periodeBulan($b['periode'] ?? null);
            $harga = NormalisasiKos::harga($b['harga'] ?? null, $periode);
            $fas = NormalisasiKos::fasilitas($b['fasilitas'] ?? null);
            $alamat = NormalisasiKos::alamat($b['alamat'] ?? null, $kampus);
            $telepon = NormalisasiKos::telepon($b['telepon'] ?? null);

            $catatan = $harga['catatan'];
            if (($b['telepon'] ?? '') !== '' && $telepon === null) {
                $catatan[] = "Nomor telepon \"{$b['telepon']}\" tidak valid, dikosongkan.";
            }
            if ($fas['lainnya'] !== []) {
                $catatan[] = 'Fasilitas lain di spreadsheet: '.implode(', ', $fas['lainnya']).'.';
            }
            if ($alamat['plus_code'] !== null) {
                $catatan[] = "Titik lokasi diambil dari Plus Code {$alamat['plus_code']}, mohon dicek di peta.";
            }
            if (($b['foto'] ?? '') !== '') {
                $catatan[] = 'Link foto di spreadsheet: '.$b['foto'];
            }

            $kunci = mb_strtolower($nama);
            if (! isset($grup[$kunci])) {
                $grup[$kunci] = [
                    'nama' => $nama,
                    'telepon' => $telepon,
                    'tipe' => NormalisasiKos::tipe($b['jenis'] ?? null),
                    'periode' => $periode,
                    'harga' => $harga['harga'],
                    'fasilitas' => $fas['fasilitas'],
                    'alamat' => $alamat['alamat'],
                    'lokasi' => $alamat['lokasi'],
                    'catatan' => $catatan,
                    'jumlah_baris' => 1,
                ];

                continue;
            }

            // Baris kembar: lengkapi yang kosong, harga ambil termurah, fasilitas digabung.
            $g = &$grup[$kunci];
            $g['jumlah_baris']++;
            $g['telepon'] ??= $telepon;
            $g['tipe'] ??= NormalisasiKos::tipe($b['jenis'] ?? null);
            $g['alamat'] ??= $alamat['alamat'];
            $g['lokasi'] ??= $alamat['lokasi'];
            $g['periode'] = $this->minNullable($g['periode'], $periode);
            $g['harga'] = $this->minNullable($g['harga'], $harga['harga']);
            $g['fasilitas'] = array_values(array_unique([...$g['fasilitas'], ...$fas['fasilitas']]));
            $g['catatan'] = array_values(array_unique([...$g['catatan'], ...$catatan]));
            unset($g);
        }

        foreach ($grup as &$g) {
            if ($g['jumlah_baris'] > 1) {
                $g['catatan'][] = "Digabung dari {$g['jumlah_baris']} baris spreadsheet dengan nama yang sama. Harga yang dipakai yang termurah.";
            }
        }

        return $grup;
    }

    private function minNullable(?int $a, ?int $b): ?int
    {
        if ($a === null) {
            return $b;
        }

        return $b === null ? $a : min($a, $b);
    }

    private function tampilkanLaporan(array $laporan, int $jumlahBaris): void
    {
        $dibuat = collect($laporan['dibuat']);

        $this->newLine();
        $this->info(($this->option('dry-run') ? '[DRY RUN, tidak ada yang disimpan] ' : '')."Baris data dibaca: {$jumlahBaris}");
        $this->line('Kos baru (Draft): '.$dibuat->count());
        $this->line('Dilewati: '.count($laporan['dilewati']));

        $this->newLine();
        $this->table(['Kelengkapan', 'Jumlah kos'], [
            ['Ada tipe', $dibuat->whereNotNull('tipe')->count()],
            ['Ada harga', $dibuat->whereNotNull('harga')->count()],
            ['Ada periode', $dibuat->whereNotNull('periode')->count()],
            ['Ada alamat', $dibuat->whereNotNull('alamat')->count()],
            ['Ada titik lokasi', $dibuat->whereNotNull('lokasi')->count()],
            ['Ada fasilitas', $dibuat->filter(fn ($d) => $d['fasilitas'] !== [])->count()],
        ]);

        foreach ($laporan['dilewati'] as $d) {
            $this->warn("Dilewati: {$d}");
        }

        $penting = array_filter($laporan['peringatan'], fn ($p) => ! str_contains($p, 'Link foto'));
        if ($penting !== []) {
            $this->newLine();
            $this->line('Catatan yang perlu dicek (juga tersimpan di kolom catatan_import tiap kos):');
            foreach ($penting as $p) {
                $this->line("  - {$p}");
            }
        }

        $this->newLine();
        $this->line('Foto dan titik lokasi dilengkapi lewat halaman Edit, lalu kos bisa diaktifkan.');
    }
}
