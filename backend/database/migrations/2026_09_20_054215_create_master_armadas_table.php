<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_armadas', function (Blueprint $t): void {
            $t->id();
            $t->string('jenis_kendaraan', 100);
            $t->string('plat_nomor', 20)->unique();
            $t->string('status_stnk', 30)->default('Aktif');
            $t->string('driver')->nullable();
            $t->string('status_operasional', 30)->default('Standby');
            $t->timestamps();
            $t->index(['status_operasional', 'jenis_kendaraan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_armadas');
    }
};
