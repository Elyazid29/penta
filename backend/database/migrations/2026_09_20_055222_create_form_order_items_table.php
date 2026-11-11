<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_order_items', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('form_order_id')->constrained('form_orders')->cascadeOnDelete();
            $t->foreignId('barang_id')->nullable()->constrained('master_barangs')->nullOnDelete();
            $t->foreignId('paket_id')->nullable()->constrained('master_pakets')->nullOnDelete();
            $t->string('nama_item');
            $t->unsignedInteger('qty')->default(1);
            $t->decimal('harga_harian', 15, 2)->default(0);
            $t->timestamps();
            $t->index('form_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_order_items');
    }
};
