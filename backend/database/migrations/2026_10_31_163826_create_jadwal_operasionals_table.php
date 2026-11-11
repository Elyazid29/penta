<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_operasionals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_order_id')->constrained('form_orders')->cascadeOnDelete();
            $table->foreignId('karyawan_id')->nullable()->constrained('master_karyawans')->nullOnDelete();
            $table->foreignId('armada_id')->nullable()->constrained('master_armadas')->nullOnDelete();
            $table->string('tanggal_mulai');
            $table->string('tanggal_selesai');
            $table->string('jobdesk')->nullable();
            $table->string('status')->default('Terjadwal');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_operasionals');
    }
};
