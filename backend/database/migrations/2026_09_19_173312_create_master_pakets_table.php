<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_pakets', function (Blueprint $t): void {
            $t->id();
            $t->string('kode_paket', 50)->unique();
            $t->string('nama_paket');
            $t->string('kategori', 100)->nullable();
            $t->decimal('harga_paket', 15, 2)->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_pakets');
    }
};
