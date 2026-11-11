<?php

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\MasterPaket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterPaketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_payload_persists_package_and_its_barang_items(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $barang = MasterBarang::query()->create([
            'kode_barang' => 'CAM-001',
            'nama_barang' => 'Kamera Studio',
            'kategori' => 'Camera',
            'jumlah_total' => 4,
            'jumlah_rusak' => 0,
            'di_sewa' => 0,
            'harga_rental' => 150000,
        ]);

        $response = $this->postJson('/api/v1/master-paket', [
            'kode_paket' => 'PKT-001',
            'nama_paket' => 'Paket Studio',
            'kategori' => 'Produksi',
            'harga_paket' => 500000,
            'items' => [['barang_id' => $barang->id, 'qty' => 2]],
        ]);

        $response->assertCreated()->assertJsonPath('data.nama_paket', 'Paket Studio');
        $paket = MasterPaket::query()->where('kode_paket', 'PKT-001')->firstOrFail();
        $this->assertDatabaseHas('master_paket_items', [
            'paket_id' => $paket->id,
            'barang_id' => $barang->id,
            'qty' => 2,
        ]);
    }
}
