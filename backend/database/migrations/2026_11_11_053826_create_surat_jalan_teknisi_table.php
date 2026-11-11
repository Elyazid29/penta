<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_jalan_teknisi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('surat_jalan_id')->constrained('surat_jalans')->cascadeOnDelete();
            $table->foreignId('karyawan_id')->constrained('master_karyawans')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['surat_jalan_id', 'karyawan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_jalan_teknisi');
    }
};
