<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_photos', function (Blueprint $table): void {
            $table->string('foto_thumb_path')->nullable()->after('foto_path');
        });

        Schema::table('delivery_additional_items', function (Blueprint $table): void {
            $table->string('foto_thumb_path')->nullable()->after('foto_path');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_photos', function (Blueprint $table): void {
            $table->dropColumn('foto_thumb_path');
        });

        Schema::table('delivery_additional_items', function (Blueprint $table): void {
            $table->dropColumn('foto_thumb_path');
        });
    }
};
