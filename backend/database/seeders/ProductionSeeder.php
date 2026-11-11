<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class ProductionSeeder extends Seeder
{
    /**
     * Seeder produksi: hanya membuat satu akun owner dari .env.
     * Wajib set OWNER_EMAIL dan OWNER_PASSWORD (minimal 12 karakter).
     */
    public function run(): void
    {
        $email = trim((string) env('OWNER_EMAIL', ''));
        $password = (string) env('OWNER_PASSWORD', '');
        $name = trim((string) env('OWNER_NAME', 'Owner')) ?: 'Owner';

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Set OWNER_EMAIL yang valid di .env sebelum menjalankan ProductionSeeder.');
        }

        if (strlen($password) < 12) {
            throw new RuntimeException('OWNER_PASSWORD minimal 12 karakter. Jangan pakai password default.');
        }

        $ownerRole = Role::firstOrCreate(
            ['name' => 'owner'],
            ['display_name' => 'Owner', 'description' => 'Super Admin - Full Access'],
        );

        Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name' => 'Admin', 'description' => 'Operasional Harian'],
        );

        Role::firstOrCreate(
            ['name' => 'teknisi'],
            ['display_name' => 'Teknisi', 'description' => 'Mobile View - Lapangan'],
        );

        User::updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => $password, 'role_id' => $ownerRole->id],
        );

        User::whereIn('email', [
            'admin@operasional.local',
            'teknisi@penta.local',
            'test@example.com',
        ])->where('email', '!=', $email)->delete();

        if ($email !== 'admin@penta.local') {
            User::where('email', 'admin@penta.local')->delete();
        }

        $this->command?->info("Akun owner siap: {$email}");
    }
}
