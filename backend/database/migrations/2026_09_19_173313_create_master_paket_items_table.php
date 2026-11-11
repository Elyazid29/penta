<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_paket_items', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('paket_id')->constrained('master_pakets')->cascadeOnDelete();
            $t->foreignId('barang_id')->constrained('master_barangs')->cascadeOnDelete();
            $t->unsignedInteger('qty')->default(1);
            $t->unique(['paket_id', 'barang_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_paket_items');
    }
};
