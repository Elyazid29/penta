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

    public function test_master_paket_can_be_updated_deleted_and_validated(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $paket = MasterPaket::query()->create([
            'kode_paket' => 'PKT-CRUD-1',
            'nama_paket' => 'Paket Awal',
            'harga_paket' => 100000,
        ]);
        $paket->items()->create(['nama_item' => 'Item Awal', 'qty' => 1, 'harga' => 100000]);

        $this->putJson('/api/v1/master-paket/'.$paket->id, [
            'kode_paket' => 'PKT-CRUD-1',
            'nama_paket' => 'Paket Update',
            'harga_paket' => 250000,
            'items' => [['nama_item' => 'Item Baru', 'qty' => 2, 'satuan' => 'unit', 'harga' => 125000]],
        ])->assertOk()->assertJsonPath('data.nama_paket', 'Paket Update');

        $this->assertDatabaseHas('master_pakets', ['id' => $paket->id, 'nama_paket' => 'Paket Update']);
        $this->assertDatabaseHas('master_paket_items', ['paket_id' => $paket->id, 'nama_item' => 'Item Baru']);
        $this->assertDatabaseMissing('master_paket_items', ['paket_id' => $paket->id, 'nama_item' => 'Item Awal']);

        $this->postJson('/api/v1/master-paket', [
            'kode_paket' => 'PKT-BAD',
            'nama_paket' => 'Bad',
            'harga_paket' => 1000,
            'items' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items']);

        $this->postJson('/api/v1/master-paket', [
            'kode_paket' => 'PKT-BAD-2',
            'nama_paket' => 'Bad 2',
            'harga_paket' => 1000,
            'items' => [['nama_item' => 'X', 'qty' => 0, 'harga' => -1]],
        ])->assertUnprocessable()->assertJsonValidationErrors(['items.0.qty', 'items.0.harga']);

        $this->deleteJson('/api/v1/master-paket/'.$paket->id)->assertNoContent();
        $this->assertDatabaseMissing('master_pakets', ['id' => $paket->id]);
    }
}
