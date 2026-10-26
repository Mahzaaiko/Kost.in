<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jaringan jalan untuk routing pejalan kaki (pgRouting).
 *
 * Satu baris = satu ruas jalan di antara dua simpul (persimpangan atau ujung jalan).
 * - source/target: id simpul. Untuk data OSM memakai id node OSM.
 * - sumber: 'osm' dari OpenStreetMap, 'survei' untuk jalan tikus hasil survei (Fase Locality).
 * - utama: ruas yang terhubung dengan jaringan terbesar. Hanya ruas ini yang dipakai
 *   untuk menempelkan titik kos dan kampus, supaya rute selalu bisa ditemukan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ruas_jalan', function (Blueprint $table) {
            $table->id();
            $table->string('sumber', 10)->default('osm');
            $table->bigInteger('osm_way_id')->nullable();
            $table->string('nama')->nullable();
            $table->string('jenis', 40);
            $table->bigInteger('source');
            $table->bigInteger('target');
            $table->double('panjang_m');
            $table->boolean('utama')->default(false);

            $table->index('source');
            $table->index('target');
            $table->index(['sumber', 'utama']);
        });

        // Geometry (bukan geography) karena pgRouting dan operator KNN bekerja di geometry.
        // Panjang dalam meter tetap dihitung lewat cast ke geography saat import.
        DB::statement('ALTER TABLE ruas_jalan ADD COLUMN geom geometry(LineString, 4326) NOT NULL');
        DB::statement('CREATE INDEX ruas_jalan_geom_index ON ruas_jalan USING GIST (geom)');
        DB::statement("ALTER TABLE ruas_jalan ADD CONSTRAINT ruas_jalan_sumber_check CHECK (sumber IN ('osm', 'survei'))");
        DB::statement('ALTER TABLE ruas_jalan ADD CONSTRAINT ruas_jalan_panjang_check CHECK (panjang_m >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ruas_jalan');
    }
};