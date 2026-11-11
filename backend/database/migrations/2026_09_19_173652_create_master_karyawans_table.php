<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_karyawans', function (Blueprint $t): void {
            $t->id();
            $t->string('nama_lengkap');
            $t->string('nik', 50)->nullable();
            $t->string('no_ktp', 50)->nullable();
            $t->string('jabatan', 100)->nullable();
            $t->string('no_telp', 50)->nullable();
            $t->string('no_rekening', 100)->nullable();
            $t->string('ktp_path')->nullable();
            $t->string('status', 20)->default('Aktif');
            $t->timestamps();
            $t->index(['status', 'jabatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_karyawans');
    }
};
