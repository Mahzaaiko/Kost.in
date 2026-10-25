<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aktifkan PostGIS (tipe geography, fungsi ST_*) dan pgRouting (pgr_*).
     * Butuh user database dengan hak CREATE EXTENSION (misalnya postgres).
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');
        DB::statement('CREATE EXTENSION IF NOT EXISTS pgrouting');
    }

    /**
     * Sengaja tidak menghapus extension: DROP EXTENSION akan ikut menghapus
     * semua kolom geography/geometry di database ini.
     */
    public function down(): void
    {
        //
    }
};
