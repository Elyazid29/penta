<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterDataCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_update_and_delete_armada(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $created = $this->postJson('/api/v1/master-armada', [
            'jenis_kendaraan' => 'Mobil Box',
            'plat_nomor' => 'B 1234 PTS',
            'status_stnk' => 'Aktif',
            'driver' => 'Andi',
            'status_operasional' => 'Standby',
        ])->assertCreated()->json('data');

        $this->patchJson('/api/v1/master-armada/'.$created['id'], [
            'jenis_kendaraan' => 'Mobil Box Besar',
            'plat_nomor' => 'B 1234 PTS',
            'status_stnk' => 'Aktif',
            'driver' => 'Budi',
            'status_operasional' => 'Jalan',
        ])->assertOk()->assertJsonPath('data.status_operasional', 'Jalan');

        $this->deleteJson('/api/v1/master-armada/'.$created['id'])->assertNoContent();
        $this->assertDatabaseMissing('master_armada', ['id' => $created['id']]);
    }

    public function test_user_can_create_update_and_delete_perusahaan(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $created = $this->postJson('/api/v1/master-perusahaan', [
            'nama_perusahaan' => 'Penta Tri Saka',
            'alamat_kantor' => 'Jakarta',
            'telepon' => '08123456789',
            'email' => 'ops@penta.test',
            'nama_bank' => 'BCA',
            'nomor_rekening' => '1234567890',
            'atas_nama_rekening' => 'Penta Tri Saka',
        ])->assertCreated()->json('data');

        $this->patchJson('/api/v1/master-perusahaan/'.$created['id'], [
            'nama_perusahaan' => 'Penta Tri Saka Indonesia',
            'alamat_kantor' => 'Jakarta',
            'telepon' => '08123456789',
            'email' => 'ops@penta.test',
        ])->assertOk()->assertJsonPath('data.nama_perusahaan', 'Penta Tri Saka Indonesia');

        $this->deleteJson('/api/v1/master-perusahaan/'.$created['id'])->assertNoContent();
        $this->assertDatabaseMissing('master_perusahaan', ['id' => $created['id']]);
    }
}
