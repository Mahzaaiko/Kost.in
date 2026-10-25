<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foto_kos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kos_id')->constrained('kos')->cascadeOnDelete();
            $table->string('path');
            $table->boolean('is_utama')->default(false);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['kos_id', 'urutan']);
        });

        // Satu kos hanya boleh punya satu foto utama.
        DB::statement('CREATE UNIQUE INDEX foto_kos_satu_utama_per_kos ON foto_kos (kos_id) WHERE is_utama');
    }

    public function down(): void
    {
        Schema::dropIfExists('foto_kos');
    }
};
