<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\TransaksiKeuangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthAndAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_logout_and_fetch_profile(): void
    {
        $role = Role::query()->where('name', 'admin')->firstOrFail();
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'secret-pass-123',
            'role_id' => $role->id,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
            'device_name' => 'test',
        ])->assertUnprocessable()->assertJsonPath('message', 'Email atau kata sandi tidak valid.');

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'secret-pass-123',
            'device_name' => 'test',
        ]);

        $login->assertOk()->assertJsonStructure(['token', 'user']);
        $token = $login->json('token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Berhasil keluar.');
    }

    public function test_protected_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/laporan/summary')->assertUnauthorized();
        $this->get('/api/v1/laporan/transactions.csv')->assertUnauthorized();
        $this->get('/api/v1/laporan/invoices.csv')->assertUnauthorized();
        $this->get('/api/v1/laporan/surat-jalan.csv')->assertUnauthorized();
        $this->get('/api/v1/laporan/jadwal-operasional.csv')->assertUnauthorized();
        $this->getJson('/api/v1/master-barang')->assertUnauthorized();
    }

    public function test_laporan_transactions_csv_can_be_downloaded(): void
    {
        Sanctum::actingAs($this->userWithRole('admin'));
        TransaksiKeuangan::query()->create([
            'tanggal' => '2026-11-10',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 500000,
            'metode' => 'Transfer',
            'referensi' => 'REF-1',
            'keterangan' => 'Uji',
        ]);

        $response = $this->get('/api/v1/laporan/transactions.csv');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Tanggal,Jenis,Kategori', $response->streamedContent());
        $this->assertStringContainsString('REF-1', $response->streamedContent());
    }

    public function test_absensi_is_restricted_to_admin_and_owner(): void
    {
        Sanctum::actingAs($this->userWithRole('teknisi'));
        $this->getJson('/api/v1/absensi')->assertForbidden();

        Sanctum::actingAs($this->userWithRole('admin'));
        $this->getJson('/api/v1/absensi')->assertOk();
    }

    public function test_teknisi_cannot_access_admin_or_owner_management_endpoints(): void
    {
        Sanctum::actingAs($this->userWithRole('teknisi'));

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->getJson('/api/v1/master-perusahaan')->assertForbidden();
        $this->getJson('/api/v1/surat-jalan')->assertForbidden();
        $this->getJson('/api/v1/jadwal-operasional')->assertForbidden();
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
