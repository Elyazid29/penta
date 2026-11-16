<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_operasional_alats', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('jadwal_operasional_id')->constrained('jadwal_operasionals')->cascadeOnDelete();
            $table->foreignId('master_barang_id')->constrained('master_barangs')->cascadeOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->timestamps();
            $table->unique(['jadwal_operasional_id', 'master_barang_id'], 'jadwal_alat_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_operasional_alats');
    }
};
