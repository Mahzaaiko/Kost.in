<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Nama pemilik tidak dipakai di Kost.in (datanya sulit didapat). */
    public function up(): void
    {
        Schema::table('kos', function (Blueprint $table) {
            $table->dropColumn('nama_pemilik');
        });
    }

    public function down(): void
    {
        Schema::table('kos', function (Blueprint $table) {
            $table->string('nama_pemilik', 100)->nullable()->after('nama');
        });
    }
};
