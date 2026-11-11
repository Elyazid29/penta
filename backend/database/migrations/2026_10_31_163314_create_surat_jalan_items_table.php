<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_jalan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('surat_jalan_id')->constrained('surat_jalans')->cascadeOnDelete();
            $table->foreignId('barang_id')->nullable()->constrained('master_barangs')->nullOnDelete();
            $table->string('nama_item');
            $table->unsignedInteger('qty')->default(1);
            $table->string('status_check')->default('Belum Check-out');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_jalan_items');
    }
};
