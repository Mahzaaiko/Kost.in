<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data awal aplikasi: master fasilitas dan akun admin.
     * Data kos tidak di-seed; dimasukkan lewat perintah kos:import.
     */
    public function run(): void
    {
        $this->call([
            FasilitasSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
