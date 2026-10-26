<?php

namespace App\Console\Commands;

use App\Services\Routing\ImportJaringanJalan;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Import jaringan jalan OpenStreetMap di sekitar kampus untuk routing pejalan kaki.
 *
 * Aman dijalankan ulang: semua ruas bersumber OSM diganti dengan data terbaru,
 * ruas hasil survei (jalan tikus) tidak disentuh.
 */
class ImportJalanOsm extends Command
{
    protected $signature = 'jalan:import-osm
        {--radius= : Radius area dari kampus dalam km (default dari config kostin.routing.radius_import_km)}
        {--dry-run : Ambil dan hitung data tanpa mengganti isi tabel ruas_jalan}
        {--segarkan : Abaikan area yang tersimpan dari percobaan sebelumnya, unduh ulang semua}';

    protected $description = 'Import jaringan jalan pejalan kaki dari OpenStreetMap (Overpass API) untuk pgRouting';

    public function handle(ImportJaringanJalan $import): int
    {
        // Satu tile Overpass yang padat bisa beberapa MB JSON.
        ini_set('memory_limit', '1024M');

        $radius = (float) ($this->option('radius') ?: config('kostin.routing.radius_import_km'));
        if ($radius <= 0 || $radius > 30) {
            $this->error('Radius harus lebih dari 0 dan maksimal 30 km.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $this->info(sprintf('Mengambil jalan OSM radius %s km dari kampus%s...', rtrim(rtrim(number_format($radius, 1, ',', ''), '0'), ','), $dryRun ? ' (dry run)' : ''));

        $bar = null;
        try {
            $hasil = $import->jalankan($radius, $dryRun, function (int $ke, int $total, string $keterangan) use (&$bar) {
                if (! $bar) {
                    $bar = $this->output->createProgressBar($total);
                    $bar->setFormat(' %current%/%max% area [%bar%] %percent:3s%% %message%');
                    $bar->start();
                }
                $bar->setMessage($keterangan);
                $bar->setProgress($ke);
            }, ! $this->option('segarkan'), function (string $pesan) use (&$bar) {
                if ($bar) {
                    $bar->finish();
                    $bar = null;
                    $this->newLine();
                }
                $this->line(' '.$pesan);
            });
        } catch (RuntimeException $e) {
            $bar?->clear();
            $this->newLine();
            $this->error($e->getMessage());
            $this->line('Tabel ruas_jalan tidak diubah.');

            return self::FAILURE;
        }

        $bar?->finish();
        $this->newLine();
        $this->line(' Selesai diolah.');
        $this->newLine();

        $this->table(['Keterangan', 'Nilai'], array_filter([
            ['Area (tile) diambil', $hasil['tile'].($hasil['tile_simpanan'] > 0 ? " ({$hasil['tile_simpanan']} dari simpanan percobaan sebelumnya)" : '')],
            ['Jalan OSM (way)', number_format($hasil['way'], 0, ',', '.')],
            ['Ruas setelah dipotong di persimpangan', number_format($hasil['ruas'], 0, ',', '.')],
            ['Total panjang', number_format($hasil['total_km'], 1, ',', '.').' km'],
            $dryRun ? null : ['Komponen jaringan', number_format($hasil['komponen'], 0, ',', '.')],
            $dryRun ? null : ['Ruas di jaringan utama', number_format($hasil['ruas_utama'], 0, ',', '.').' ('.number_format($hasil['km_utama'], 1, ',', '.').' km)'],
        ]));

        if ($dryRun) {
            $this->warn('Dry run: tabel ruas_jalan tidak diubah.');
        } else {
            $this->info('Selesai. Cache rute lama otomatis tidak dipakai lagi.');
            $this->line('Lanjutkan dengan: php artisan rute:cek');
        }

        return self::SUCCESS;
    }
}