<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_barangs', function (Blueprint $table): void {
            $table->string('satuan', 30)->default('unit')->after('kategori');
        });
    }

    public function down(): void
    {
        Schema::table('master_barangs', function (Blueprint $table): void {
            $table->dropColumn('satuan');
        });
    }
};
