<?php

namespace Tests\Feature;

use App\Models\MasterKlien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterKlienApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_payload_persists_client_and_contact_persons(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/master-klien', [
            'nama_klien' => 'PT Contoh',
            'nama_perusahaan' => 'PT Contoh Indonesia',
            'alamat' => 'Jakarta',
            'cp1_nama' => 'Budi',
            'cp1_telp' => '081234567890',
            'cp2_nama' => 'Sari',
            'cp2_telp' => '081234567891',
            'cp3_nama' => '',
            'cp3_telp' => '',
            'npwp' => '01.234.567.8-999.000',
            'kategori' => 'Corporate',
        ]);

        $response->assertCreated()->assertJsonPath('data.contacts.0.nama', 'Budi');
        $this->assertDatabaseHas('master_kliens', [
            'nama_klien' => 'PT Contoh',
            'cp1_nama' => 'Budi',
            'cp2_nama' => 'Sari',
            'kategori' => 'Corporate',
        ]);
        $this->assertSame(1, MasterKlien::query()->where('nama_klien', 'PT Contoh')->count());
    }

    public function test_master_klien_can_be_updated_deleted_and_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $klien = MasterKlien::query()->create(['nama_klien' => 'Klien Lama', 'kategori' => 'Personal']);

        $this->putJson('/api/v1/master-klien/'.$klien->id, [
            'nama_klien' => 'Klien Baru',
            'nama_perusahaan' => 'PT Baru',
            'kategori' => 'Corporate',
        ])->assertOk()->assertJsonPath('data.nama_klien', 'Klien Baru');

        $this->assertDatabaseHas('master_kliens', ['id' => $klien->id, 'nama_klien' => 'Klien Baru', 'kategori' => 'Corporate']);

        $this->postJson('/api/v1/master-klien', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['nama_klien']);

        $this->deleteJson('/api/v1/master-klien/'.$klien->id)->assertNoContent();
        $this->assertDatabaseMissing('master_kliens', ['id' => $klien->id]);
    }
}
