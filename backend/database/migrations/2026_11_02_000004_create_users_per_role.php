<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('email', 'admin@penta.local')->update(['role_id' => 1]);

        DB::table('users')->insert([
            [
                'name' => 'Admin Operasional',
                'email' => 'admin@operasional.local',
                'password' => Hash::make('password'),
                'role_id' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Teknisi Lapangan',
                'email' => 'teknisi@penta.local',
                'password' => Hash::make('password'),
                'role_id' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        DB::table('users')->whereIn('email', ['admin@operasional.local', 'teknisi@penta.local'])->delete();
    }
};
