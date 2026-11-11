<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('roles')->insert([
            ['name' => 'owner', 'display_name' => 'Owner', 'description' => 'Super Admin - Full Access', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin', 'display_name' => 'Admin', 'description' => 'Operasional Harian', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'teknisi', 'display_name' => 'Teknisi', 'description' => 'Mobile View - Lapangan', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('users')->where('email', 'admin@penta.local')->update(['role_id' => 1]);
    }

    public function down(): void
    {
        DB::table('roles')->truncate();
    }
};
