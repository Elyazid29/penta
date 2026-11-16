<?php

namespace Tests\Feature;

use App\Models\FormOrder;
use App\Models\MasterKlien;
use App\Models\Role;
use App\Models\SuratJalan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthorizationAndUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_manage_users_and_non_owner_cannot(): void
    {
        $owner = $this->actingAsRole('owner');
        $teknisiRole = Role::query()->where('name', 'teknisi')->firstOrFail();

        $this->getJson('/api/v1/users')->assertOk();
        $this->getJson('/api/v1/roles')->assertOk();

        $created = $this->postJson('/api/v1/users', [
            'name' => 'Teknisi Baru',
            'email' => 'baru@example.com',
            'password' => 'password12345',
            'role_id' => $teknisiRole->id,
        ]);

        $created->assertCreated()->assertJsonPath('data.email', 'baru@example.com');
        $newUserId = $created->json('data.id');

        $this->putJson("/api/v1/users/{$newUserId}", [
            'name' => 'Teknisi Update',
        ])->assertOk()->assertJsonPath('data.name', 'Teknisi Update');

        $this->deleteJson("/api/v1/users/{$owner->id}")
            ->assertForbidden()
            ->assertJsonPath('message', 'Tidak bisa menghapus akun sendiri.');

        $this->deleteJson("/api/v1/users/{$newUserId}")->assertNoContent();

        $admin = $this->actingAsRole('admin');
        $this->getJson('/api/v1/users')->assertForbidden();
        $this->postJson('/api/v1/users', [
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'password12345',
            'role_id' => $teknisiRole->id,
        ])->assertForbidden();

        // ensure acting user changed is admin (sanity)
        $this->assertSame($admin->id, auth()->id());
    }

    public function test_teknisi_cannot_manage_surat_jalan_but_can_access_delivery_endpoints(): void
    {
        $order = $this->makeOrder();
        $suratJalan = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-AUTH-001',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => now(),
            'status' => 'Draft',
        ]);

        $this->actingAsRole('teknisi');

        $this->getJson('/api/v1/surat-jalan')->assertForbidden();
        $this->postJson('/api/v1/surat-jalan', [
            'nomor_surat_jalan' => 'SJ-NEW',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => now()->toDateTimeString(),
            'status' => 'Draft',
        ])->assertForbidden();

        $this->postJson('/api/v1/delivery/scan-surat-jalan', [
            'kode_surat_jalan' => $suratJalan->nomor_surat_jalan,
        ])->assertOk()->assertJsonPath('data.nomor_surat_jalan', 'SJ-AUTH-001');

        $this->getJson("/api/v1/surat-jalan/{$suratJalan->id}/photos")->assertOk();

        $this->actingAsRole('admin');
        $this->getJson('/api/v1/surat-jalan')->assertOk();
    }

    private function actingAsRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function makeOrder(): FormOrder
    {
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Auth']);

        return FormOrder::query()->create([
            'nomor_order' => 'ORD-AUTH-'.uniqid(),
            'klien_id' => $klien->id,
            'nama_event' => 'Event Auth',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
    }
}
