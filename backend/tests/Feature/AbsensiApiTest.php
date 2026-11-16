<?php

namespace Tests\Feature;

use App\Models\Absensi;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AbsensiApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_update_and_delete_absensi(): void
    {
        Sanctum::actingAs($this->userWithRole('admin'));
        $target = User::factory()->create();

        $created = $this->postJson('/api/v1/absensi', [
            'user_id' => $target->id,
            'tanggal' => '2026-11-16',
            'status' => 'Hadir',
            'keterangan' => 'Masuk tepat waktu',
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.user_id', $target->id)
            ->assertJsonPath('data.status', 'Hadir');

        $id = $created->json('data.id');
        $this->assertDatabaseHas('absensis', ['id' => $id, 'status' => 'Hadir']);

        $this->putJson('/api/v1/absensi/'.$id, [
            'status' => 'Izin',
            'keterangan' => 'Keperluan keluarga',
        ])->assertOk()
            ->assertJsonPath('data.status', 'Izin');

        $this->assertDatabaseHas('absensis', ['id' => $id, 'status' => 'Izin', 'keterangan' => 'Keperluan keluarga']);

        $this->deleteJson('/api/v1/absensi/'.$id)->assertNoContent();
        $this->assertDatabaseMissing('absensis', ['id' => $id]);
    }

    public function test_absensi_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs($this->userWithRole('admin'));
        $target = User::factory()->create();

        $this->postJson('/api/v1/absensi', [
            'user_id' => $target->id,
            'tanggal' => '2026-11-16',
            'status' => 'Unknown',
        ])->assertUnprocessable()->assertJsonValidationErrors(['status']);

        $this->postJson('/api/v1/absensi', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'tanggal', 'status']);

        $absensi = Absensi::query()->create([
            'user_id' => $target->id,
            'tanggal' => '2026-11-16',
            'status' => 'Hadir',
        ]);

        $this->putJson('/api/v1/absensi/'.$absensi->id, ['status' => 'Invalid'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }
}
