<?php

namespace App\Console\Commands;

use App\Models\FotoKos;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Salin file foto kos dari disk lokal "public" ke disk lain (default s3 / Neon Object Storage).
 * Dipakai sekali saat pindah ke server. Aman dijalankan ulang: file yang sudah ada dilewati.
 */
class SalinFoto extends Command
{
    protected $signature = 'foto:salin
        {--ke=s3 : Disk tujuan}
        {--timpa : Timpa file yang sudah ada di tujuan}';

    protected $description = 'Salin foto kos dari storage lokal (disk public) ke disk tujuan, misalnya s3';

    public function handle(): int
    {
        $asal = Storage::disk('public');
        $tujuan = Storage::disk($this->option('ke'));

        $berhasil = $dilewati = 0;
        $hilang = [];

        $bar = $this->output->createProgressBar(FotoKos::count());
        foreach (FotoKos::orderBy('id')->cursor() as $foto) {
            $bar->advance();
            if (! $asal->exists($foto->path)) {
                $hilang[] = $foto->path;
                continue;
            }
            if (! $this->option('timpa') && $tujuan->exists($foto->path)) {
                $dilewati++;
                continue;
            }
            $tujuan->writeStream($foto->path, $asal->readStream($foto->path));
            $berhasil++;
        }
        $bar->finish();
        $this->newLine(2);

        $this->info("Disalin: {$berhasil}, dilewati (sudah ada): {$dilewati}, file lokal tidak ditemukan: ".count($hilang));
        foreach ($hilang as $p) {
            $this->warn("  tidak ada di lokal: {$p}");
        }

        return $hilang === [] ? self::SUCCESS : self::FAILURE;
    }
}
