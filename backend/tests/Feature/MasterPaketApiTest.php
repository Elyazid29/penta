<?php

namespace Tests\Feature;

use App\Models\MasterPaket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterPaketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_payload_persists_package_with_standalone_items(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/master-paket', [
            'kode_paket' => 'PKT-001',
            'nama_paket' => 'Paket Studio',
            'kategori' => 'Produksi',
            'harga_paket' => 500000,
            'items' => [['nama_item' => 'Kamera Studio', 'qty' => 2, 'satuan' => 'unit', 'harga' => 150000]],
        ]);

        $response->assertCreated()->assertJsonPath('data.nama_paket', 'Paket Studio');
        $paket = MasterPaket::query()->where('kode_paket', 'PKT-001')->firstOrFail();
        $this->assertDatabaseHas('master_paket_items', [
            'paket_id' => $paket->id,
            'barang_id' => null,
            'nama_item' => 'Kamera Studio',
            'qty' => 2,
        ]);
    }
}
