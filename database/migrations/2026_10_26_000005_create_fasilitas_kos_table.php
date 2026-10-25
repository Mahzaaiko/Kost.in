<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fasilitas_kos', function (Blueprint $table) {
            $table->foreignId('kos_id')->constrained('kos')->cascadeOnDelete();
            $table->foreignId('fasilitas_id')->constrained('fasilitas')->cascadeOnDelete();
            $table->primary(['kos_id', 'fasilitas_id']);
            $table->index('fasilitas_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fasilitas_kos');
    }
};
