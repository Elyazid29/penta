<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_orders', function (Blueprint $t): void {
            $t->id();
            $t->string('nomor_order', 50)->unique();
            $t->foreignId('klien_id')->constrained('master_kliens');
            $t->string('nama_event');
            $t->string('venue');
            $t->string('ruangan')->nullable();
            $t->string('lantai')->nullable();
            $t->dateTime('load_in');
            $t->dateTime('load_out');
            $t->string('status', 30)->default('Draft');
            $t->text('catatan')->nullable();
            $t->timestamps();
            $t->index(['status', 'load_in']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_orders');
    }
};
