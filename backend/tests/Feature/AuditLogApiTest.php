<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\MasterKlien;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_is_recorded_on_model_changes(): void
    {
        $admin = $this->actingAsRole('admin');

        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Audit']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => MasterKlien::class,
            'auditable_id' => $klien->id,
        ]);
        $created = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klien->id)->where('action', 'created')->first();
        $this->assertNotNull($created);
        $this->assertSame('PT Audit', $created->new_values['nama_klien'] ?? null);

        $klien->update(['nama_klien' => 'PT Audit Baru']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'updated',
            'auditable_type' => MasterKlien::class,
            'auditable_id' => $klien->id,
        ]);
        $updated = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klien->id)->where('action', 'updated')->latest('id')->first();
        $this->assertNotNull($updated);
        $this->assertSame('PT Audit', $updated->old_values['nama_klien'] ?? null);
        $this->assertSame('PT Audit Baru', $updated->new_values['nama_klien'] ?? null);

        $klienId = $klien->id;
        $klien->delete();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'deleted',
            'auditable_type' => MasterKlien::class,
            'auditable_id' => $klienId,
        ]);
        $deleted = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klienId)->where('action', 'deleted')->latest('id')->first();
        $this->assertNotNull($deleted);
        $this->assertSame('PT Audit Baru', $deleted->old_values['nama_klien'] ?? null);
    }

    public function test_audit_log_endpoint_is_restricted_to_admin_and_owner(): void
    {
        $this->getJson('/api/v1/audit-logs')->assertUnauthorized();

        $this->actingAsRole('teknisi');
        $this->getJson('/api/v1/audit-logs')->assertForbidden();

        $this->actingAsRole('admin');
        $this->getJson('/api/v1/audit-logs')->assertOk()->assertJsonStructure(['data']);

        $this->actingAsRole('owner');
        $this->getJson('/api/v1/audit-logs')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_audit_log_can_be_filtered(): void
    {
        $this->actingAsRole('admin');
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Filter']);
        $klien->update(['nama_klien' => 'PT Filter Updated']);

        $response = $this->getJson('/api/v1/audit-logs?action=updated&auditable_type='.urlencode(MasterKlien::class))
            ->assertOk()
            ->assertJsonPath('data.0.action', 'updated')
            ->assertJsonPath('data.0.auditable_type', MasterKlien::class);

        $this->assertNotNull($response->json('data.0.user'));
    }

    public function test_user_model_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $role = Role::query()->where('name', 'teknisi')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'name' => 'Teknisi Audit']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_audit_log_endpoint_returns_paginated_structure(): void
    {
        $this->actingAsRole('admin');
        MasterKlien::query()->create(['nama_klien' => 'PT Paging']);

        $this->getJson('/api/v1/audit-logs?per_page=10')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'current_page', 'per_page', 'total']);
    }

    public function test_audit_log_skips_update_when_no_changes(): void
    {
        $this->actingAsRole('admin');
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Sama']);
        $before = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klien->id)->where('action', 'updated')->count();

        $klien->update(['nama_klien' => 'PT Sama']);

        $after = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klien->id)->where('action', 'updated')->count();
        $this->assertSame($before, $after);
    }

    private function actingAsRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);
        Sanctum::actingAs($user);

        return $user;
    }
}
