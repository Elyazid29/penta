<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeder untuk pengembangan lokal.
     * Akun demo memakai password "password" — JANGAN dipakai di produksi.
     * Untuk produksi gunakan: php artisan db:seed --class=ProductionSeeder
     */
    public function run(): void
    {
        $ownerRole = Role::firstOrCreate(
            ['name' => 'owner'],
            ['display_name' => 'Owner', 'description' => 'Super Admin - Full Access'],
        );
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Operasional Harian'],
        );
        $teknisiRole = Role::firstOrCreate(
            ['name' => 'teknisi'],
            ['display_name' => 'Teknisi', 'description' => 'Mobile View - Lapangan'],
        );

        User::updateOrCreate(
            ['email' => 'admin@penta.local'],
            ['name' => 'Penta Owner', 'password' => 'password', 'role_id' => $ownerRole->id],
        );

        User::updateOrCreate(
            ['email' => 'admin@operasional.local'],
            ['name' => 'Admin Operasional', 'password' => 'password', 'role_id' => $adminRole->id],
        );

        User::updateOrCreate(
            ['email' => 'teknisi@penta.local'],
            ['name' => 'Teknisi Lapangan', 'password' => 'password', 'role_id' => $teknisiRole->id],
        );

        User::where('email', 'test@example.com')->delete();
    }
}
