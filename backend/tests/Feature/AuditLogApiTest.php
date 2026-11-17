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

        $log = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klien->id)->first();
        $this->assertNotNull($log);
        $this->assertSame('PT Audit', $log->new_values['nama_klien'] ?? null);
    }

    public function test_audit_log_endpoint_is_restricted_to_admin_and_owner(): void
    {
        $this->actingAsRole('teknisi');
        $this->getJson('/api/v1/audit-logs')->assertForbidden();

        $this->actingAsRole('admin');
        $this->getJson('/api/v1/audit-logs')->assertOk()->assertJsonStructure(['data']);
    }

    private function actingAsRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);
        Sanctum::actingAs($user);

        return $user;
    }
}
