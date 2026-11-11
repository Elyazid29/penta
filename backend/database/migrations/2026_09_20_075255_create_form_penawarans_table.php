<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_penawarans', function (Blueprint $t): void {
            $t->id();
            $t->string('nomor_penawaran', 50)->unique();
            $t->foreignId('form_order_id')->constrained('form_orders')->cascadeOnDelete();
            $t->date('tanggal_penawaran');
            $t->date('berlaku_sampai')->nullable();
            $t->string('status', 30)->default('Draft');
            $t->decimal('subtotal', 15, 2)->default(0);
            $t->decimal('diskon', 15, 2)->default(0);
            $t->decimal('pajak', 15, 2)->default(0);
            $t->decimal('total', 15, 2)->default(0);
            $t->text('catatan')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_penawarans');
    }
};
