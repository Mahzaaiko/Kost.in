<?php

namespace App\Console\Commands;

use App\Services\Routing\JangkauanJalanKaki;
use Illuminate\Console\Command;

/**
 * Hitung lebih dulu area slider jarak (0,1 km sampai batas) supaya slider di Jelajah langsung
 * tampil tanpa menunggu. Jalankan setelah deploy, import OSM, atau unggah gang.
 */
class SiapkanJangkauan extends Command
{
    protected $signature = 'peta:siapkan-jangkauan {--maks= : batas jarak dalam km (default kostin.jarak_maks_km)}';

    protected $description = 'Hitung lebih dulu area jangkauan slider jarak setiap 0,1 km';

    public function handle(JangkauanJalanKaki $jangkauan): int
    {
        $maks = (float) ($this->option('maks') ?: config('kostin.jarak_maks_km'));
        $langkah = (int) round($maks * 10);

        $this->withProgressBar(range(1, $langkah), fn (int $i) => $jangkauan->untukJarak($i / 10));
        $this->newLine();
        $this->info("Area 0,1 sampai {$maks} km siap.");

        return self::SUCCESS;
    }
}
