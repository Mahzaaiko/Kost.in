<?php

use App\Services\Routing\JarakRuteKos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jarak rute jalan kaki kos ke kampus (meter), dipakai filter waktu jalan dan kriteria jarak SAW.
 * jarak_rute_versi = versi jaringan jalan saat nilai dihitung, untuk mengenali nilai usang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kos', function (Blueprint $table) {
            $table->integer('jarak_rute_m')->nullable();
            $table->string('jarak_rute_versi', 40)->nullable();
        });

        // Isi langsung bila jaringan jalan sudah diimpor, supaya pencarian pertama tidak menunggu.
        if (DB::table('ruas_jalan')->exists()) {
            app(JarakRuteKos::class)->perbaruiYangUsang();
        }
    }

    public function down(): void
    {
        Schema::table('kos', function (Blueprint $table) {
            $table->dropColumn(['jarak_rute_m', 'jarak_rute_versi']);
        });
    }
};
