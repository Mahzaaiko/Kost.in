<?php

use Database\Seeders\FasilitasSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Isi ikon untuk master fasilitas yang sebelumnya memakai ikon centang default.
 *
 * Hanya mengubah fasilitas yang namanya ada di FasilitasSeeder::DAFTAR, dan hanya bila ikonnya
 * masih kosong atau masih ikon bawaan lama (parkir untuk Parkir Motor/Mobil, keamanan untuk CCTV).
 * Ikon yang sudah dipilih admin secara manual tidak ditimpa.
 */
return new class extends Migration
{
    /** Ikon bawaan lama yang boleh diganti dengan ikon yang lebih spesifik. */
    private const IKON_LAMA = [
        'Parkir Motor' => 'parkir',
        'Parkir Mobil' => 'parkir',
        'CCTV' => 'keamanan',
    ];

    public function up(): void
    {
        foreach (FasilitasSeeder::DAFTAR as $nama => $ikon) {
            DB::table('fasilitas')
                ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])
                ->where(function ($q) use ($nama) {
                    $q->whereNull('ikon');
                    if (isset(self::IKON_LAMA[$nama])) {
                        $q->orWhere('ikon', self::IKON_LAMA[$nama]);
                    }
                })
                ->update(['ikon' => $ikon, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan: ikon lama hanya ikon centang default, tidak ada data yang hilang.
    }
};