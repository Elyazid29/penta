<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_paket_items', function (Blueprint $table): void {
            $table->decimal('qty', 10, 2)->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('master_paket_items', function (Blueprint $table): void {
            $table->unsignedInteger('qty')->default(1)->change();
        });
    }
};
