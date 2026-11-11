<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_barangs', function (Blueprint $t): void {
            $t->id();
            $t->string('kode_barang', 50)->unique();
            $t->string('nama_barang');
            $t->string('kategori', 100);
            $t->text('deskripsi')->nullable();
            $t->string('foto_path')->nullable();
            $t->unsignedInteger('jumlah_total')->default(0);
            $t->unsignedInteger('jumlah_rusak')->default(0);
            $t->unsignedInteger('di_sewa')->default(0);
            $t->decimal('harga_rental', 15, 2)->default(0);
            $t->timestamps();
            $t->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_barangs');
    }
};
