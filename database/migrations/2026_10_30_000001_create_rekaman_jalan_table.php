<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rekaman gang hasil survei lapangan (Fase Locality).
 *
 * Satu rekaman = satu gang dari jalan ke jalan (atau gang buntu), diunggah admin dari file GPX/GeoJSON.
 * Gang tidak terikat ke kos tertentu: setelah disambungkan, gang menjadi bagian jaringan jalan
 * dan dipakai rute semua kos.
 *
 * Di ruas_jalan, setiap rekaman menghasilkan:
 * - satu ruas 'survei' (gang itu sendiri), dan
 * - ruas 'sambungan' bila ujung gang menempel di tengah ruas lain. Ruas sambungan adalah dua potongan
 *   ruas itu (dari kedua ujungnya sampai titik tempel). Ruas aslinya tidak dihapus, hanya ditandai
 *   terpecah supaya rute memakai potongannya. Rute "OSM saja" tetap memakai ruas OSM aslinya.
 * Semua ruas itu ikut terhapus bila rekamannya dihapus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekaman_jalan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->nullable();
            $table->string('catatan', 500)->nullable();
            $table->string('nama_file');
            $table->unsignedInteger('jumlah_titik');
            $table->double('panjang_m');
            $table->unsignedTinyInteger('ujung_tersambung')->default(0);
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE rekaman_jalan ADD COLUMN geom geometry(LineString, 4326) NOT NULL');
        DB::statement('CREATE INDEX rekaman_jalan_geom_index ON rekaman_jalan USING GIST (geom)');

        Schema::table('ruas_jalan', function (Blueprint $table) {
            $table->foreignId('rekaman_id')->nullable()->after('osm_way_id')->constrained('rekaman_jalan')->cascadeOnDelete();
            // true = ruas ini sudah dipotong menjadi ruas sambungan, jadi rute memakai potongannya.
            $table->boolean('terpecah')->default(false)->after('utama');
        });

        DB::statement('ALTER TABLE ruas_jalan DROP CONSTRAINT ruas_jalan_sumber_check');
        DB::statement("ALTER TABLE ruas_jalan ADD CONSTRAINT ruas_jalan_sumber_check CHECK (sumber IN ('osm', 'survei', 'sambungan'))");
    }

    public function down(): void
    {
        DB::delete("DELETE FROM ruas_jalan WHERE sumber <> 'osm'");
        DB::statement('ALTER TABLE ruas_jalan DROP CONSTRAINT ruas_jalan_sumber_check');
        DB::statement("ALTER TABLE ruas_jalan ADD CONSTRAINT ruas_jalan_sumber_check CHECK (sumber IN ('osm', 'survei'))");

        Schema::table('ruas_jalan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rekaman_id');
            $table->dropColumn('terpecah');
        });

        Schema::dropIfExists('rekaman_jalan');
    }
};