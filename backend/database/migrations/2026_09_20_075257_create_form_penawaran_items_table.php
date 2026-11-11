<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_penawaran_items', function (Blueprint $t): void {
            $t->id();
            $t->foreignId('form_penawaran_id')->constrained('form_penawarans')->cascadeOnDelete();
            $t->string('nama_item');
            $t->unsignedInteger('qty');
            $t->decimal('harga', 15, 2);
            $t->decimal('subtotal', 15, 2);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_penawaran_items');
    }
};
