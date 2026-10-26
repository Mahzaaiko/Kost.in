<?php

namespace App\Console\Commands;

use App\Enums\StatusKos;
use App\Models\Kos;
use App\Services\Routing\RuteService;
use App\Support\Format;
use App\Support\Koordinat;
use Illuminate\Console\Command;

/**
 * Pastikan setiap kos bisa dirutekan ke kampus, sekaligus bandingkan panjang rute
 * dengan jarak garis lurus.
 */
class CekRute extends Command
{
    protected $signature = 'rute:cek
        {--semua : Ikut cek kos Draft yang sudah punya titik lokasi}';

    protected $description = 'Cek rute jalan kaki setiap kos ke kampus dan bandingkan dengan jarak garis lurus';

    public function handle(RuteService $rute): int
    {
        $query = Kos::query()->whereNotNull('lokasi')->orderBy('nama');
        if (! $this->option('semua')) {
            $query->where('status', StatusKos::Aktif->value);
        }

        $daftar = $query->get();
        if ($daftar->isEmpty()) {
            $this->warn('Tidak ada kos dengan titik lokasi untuk dicek.');

            return self::SUCCESS;
        }

        $kampus = Koordinat::kampus();
        $batas = (float) config('kostin.routing.peringatan_jarak_ke_jalan_m');
        $baris = [];
        $gagal = 0;
        $jauhDariJalan = 0;

        foreach ($daftar as $kos) {
            $hasil = $rute->untukKos($kos);
            $lurus = $kos->lokasi->jarakMeterKe($kampus);

            if (! $hasil['tersedia']) {
                $gagal++;
                $baris[] = [$kos->nama, $kos->status->label(), Format::jarak($lurus), '-', '-', '-', $hasil['alasan']];

                continue;
            }

            $catatan = '';
            if ($hasil['jarak_ke_jalan_m'] > $batas) {
                $jauhDariJalan++;
                $catatan = 'Jauh dari jalan terpetakan';
            }

            $baris[] = [
                $kos->nama,
                $kos->status->label(),
                Format::jarak($lurus),
                Format::jarak($hasil['panjang_m']),
                $lurus > 0 ? number_format($hasil['panjang_m'] / $lurus, 2, ',', '') : '-',
                $hasil['jarak_ke_jalan_m'].' m',
                $catatan,
            ];
        }

        $this->table(['Kos', 'Status', 'Garis lurus', 'Rute jalan', 'Rasio', 'Ke jalan', 'Catatan'], $baris);

        $berhasil = $daftar->count() - $gagal;
        $this->line("Berhasil dirutekan: {$berhasil} dari {$daftar->count()} kos.");
        if ($jauhDariJalan > 0) {
            $this->warn("{$jauhDariJalan} kos berjarak lebih dari {$batas} m dari jalan terpetakan. Cek titiknya, atau catat sebagai kandidat survei jalan tikus.");
        }
        if ($gagal > 0) {
            $this->error("{$gagal} kos tidak bisa dirutekan.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}