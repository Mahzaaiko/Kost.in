<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kos', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('nama_pemilik', 100)->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->text('deskripsi')->nullable();

            // Kolom wajib untuk status aktif dibiarkan nullable karena data
            // hasil import masuk sebagai draft dan boleh belum lengkap.
            $table->enum('tipe', ['putri', 'putra', 'campur'])->nullable();
            $table->unsignedInteger('harga_per_bulan')->nullable();
            $table->unsignedSmallInteger('periode_minimal_bulan')->nullable();

            // Titik kos dalam WGS84. Disimpan sebagai geography supaya
            // ST_Distance/ST_DWithin langsung menghasilkan meter.
            $table->geography('lokasi', subtype: 'point', srid: 4326)->nullable();

            $table->enum('status', ['draft', 'aktif'])->default('draft');
            $table->unsignedInteger('jumlah_dilihat')->default(0);
            $table->text('catatan_import')->nullable();
            $table->timestamps();

            $table->spatialIndex('lokasi');
            $table->index('status');
            $table->index('harga_per_bulan');
        });

        DB::statement('ALTER TABLE kos ADD CONSTRAINT kos_periode_minimal_bulan_check CHECK (periode_minimal_bulan IN (1, 3, 6, 12))');
        DB::statement('ALTER TABLE kos ADD CONSTRAINT kos_harga_per_bulan_check CHECK (harga_per_bulan > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('kos');
    }
};
