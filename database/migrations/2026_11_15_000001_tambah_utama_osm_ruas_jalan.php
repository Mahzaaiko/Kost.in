<?php

use App\Services\Routing\ImportJaringanJalan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda komponen terbesar khusus jaringan OSM saja.
 *
 * Kolom utama dihitung di jaringan lengkap. Setelah gang survei diunggah, potongan jalan OSM
 * yang tadinya terpisah bisa ikut utama karena tersambung lewat gang. Di rute pembanding
 * "OSM saja" potongan itu terputus, sehingga titik kos yang menempel ke sana tidak punya rute.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ruas_jalan', function (Blueprint $table) {
            $table->boolean('utama_osm')->default(false);
        });

        if (DB::table('ruas_jalan')->exists()) {
            $import = app(ImportJaringanJalan::class);
            $import->tandaiKomponenUtama();
            $import->gantiVersi();
        }
    }

    public function down(): void
    {
        Schema::table('ruas_jalan', function (Blueprint $table) {
            $table->dropColumn('utama_osm');
        });
    }
};
