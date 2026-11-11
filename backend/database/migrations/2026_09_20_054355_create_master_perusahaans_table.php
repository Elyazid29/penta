<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_perusahaans', function (Blueprint $t): void {
            $t->id();
            $t->string('nama_perusahaan');
            $t->text('alamat_kantor')->nullable();
            $t->string('telepon', 50)->nullable();
            $t->string('email')->nullable();
            $t->string('kop_surat_path')->nullable();
            $t->string('logo_path')->nullable();
            $t->string('nama_bank')->nullable();
            $t->string('nomor_rekening')->nullable();
            $t->string('atas_nama_rekening')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_perusahaans');
    }
};
