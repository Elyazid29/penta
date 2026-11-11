<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surat_jalans', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor_surat_jalan', 50)->unique();
            $table->foreignId('form_order_id')->constrained('form_orders')->cascadeOnDelete();
            $table->string('tanggal_berangkat');
            $table->string('status')->default('Draft');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surat_jalans');
    }
};
