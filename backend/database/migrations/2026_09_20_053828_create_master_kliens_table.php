<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_kliens', function (Blueprint $t): void {
            $t->id();
            $t->string('nama_klien');
            $t->string('nama_perusahaan')->nullable();
            $t->text('alamat')->nullable();
            $t->string('cp1_nama', 100)->nullable();
            $t->string('cp1_telp', 50)->nullable();
            $t->string('cp2_nama', 100)->nullable();
            $t->string('cp2_telp', 50)->nullable();
            $t->string('cp3_nama', 100)->nullable();
            $t->string('cp3_telp', 50)->nullable();
            $t->string('npwp', 100)->nullable();
            $t->string('kategori', 100)->nullable();
            $t->timestamps();
            $t->index('nama_perusahaan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_kliens');
    }
};
