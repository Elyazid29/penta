<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('surat_jalan_id')->constrained('surat_jalans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('tipe', ['checkout', 'checkin']);
            $table->string('foto_path');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_item_checklists', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('delivery_photo_id')->constrained('delivery_photos')->cascadeOnDelete();
            $table->foreignId('surat_jalan_item_id')->nullable();
            $table->string('nama_barang');
            $table->integer('qty');
            $table->enum('status_fisik', ['Baik', 'Lecet', 'Rusak', 'Hilang'])->default('Baik');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('delivery_additional_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('surat_jalan_id')->constrained('surat_jalans')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nama_barang');
            $table->integer('qty');
            $table->string('foto_path')->nullable();
            $table->text('alasan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_additional_items');
        Schema::dropIfExists('delivery_item_checklists');
        Schema::dropIfExists('delivery_photos');
    }
};
