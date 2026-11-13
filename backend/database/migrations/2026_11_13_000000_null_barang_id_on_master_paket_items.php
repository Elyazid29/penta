<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Item paket berdiri sendiri, tidak lagi terikat ke master_barangs.
     */
    public function up(): void
    {
        DB::table('master_paket_items')->update(['barang_id' => null]);
    }

    public function down(): void
    {
        // Tidak ada data lama yang perlu dikembalikan.
    }
};
