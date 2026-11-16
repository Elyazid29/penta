<?php

namespace Tests\Feature;

use App\Models\MasterBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterBarangApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_payload_stores_barang_and_its_photo(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());

        $response = $this->post('/api/v1/master-barang', [
            'kode_barang' => 'CAM-001',
            'nama_barang' => 'Kamera Studio',
            'kategori' => 'Camera',
            'deskripsi' => 'Kamera untuk studio',
            'jumlah_total' => 5,
            'jumlah_rusak' => 1,
            'di_sewa' => 2,
            'harga_rental' => 250000,
            'foto' => UploadedFile::fake()->create('kamera.jpg', 100, 'image/jpeg'),
        ]);

        $response->assertCreated()->assertJsonPath('data.nama_barang', 'Kamera Studio');
        $barang = MasterBarang::query()->where('kode_barang', 'CAM-001')->firstOrFail();
        $this->assertSame(5, $barang->jumlah_total);
        $this->assertNotNull($barang->foto_path);
        Storage::disk('public')->assertExists($barang->foto_path);
    }

    public function test_valid_update_changes_barang_and_preserves_existing_photo(): void
    {
        $barang = MasterBarang::factory()->create([
            'kode_barang' => 'CAM-002',
            'nama_barang' => 'Kamera Awal',
            'kategori' => 'Camera',
            'jumlah_total' => 3,
            'jumlah_rusak' => 0,
            'di_sewa' => 0,
            'harga_rental' => 100000,
            'foto_path' => 'barang/existing.jpg',
        ]);
        Sanctum::actingAs(User::factory()->create());

        $response = $this->patchJson('/api/v1/master-barang/'.$barang->id, [
            'kode_barang' => $barang->kode_barang,
            'nama_barang' => 'Nama Diperbarui',
            'kategori' => $barang->kategori,
            'deskripsi' => $barang->deskripsi,
            'jumlah_total' => 8,
            'jumlah_rusak' => 0,
            'di_sewa' => 1,
            'harga_rental' => 300000,
        ]);

        $response->assertOk()->assertJsonPath('data.nama_barang', 'Nama Diperbarui');
        $this->assertDatabaseHas('master_barangs', [
            'id' => $barang->id,
            'jumlah_total' => 8,
            'foto_path' => 'barang/existing.jpg',
        ]);
    }

    public function test_master_barang_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/master-barang');

        $response->assertUnauthorized();
    }

    public function test_master_barang_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/master-barang', [
            'kode_barang' => 'BRG-BAD',
            'nama_barang' => 'Barang',
            'kategori' => 'Audio',
            'jumlah_total' => 5,
            'jumlah_rusak' => 6,
            'di_sewa' => 0,
            'harga_rental' => -1,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['jumlah_rusak', 'harga_rental']);

        $this->postJson('/api/v1/master-barang', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['kode_barang', 'nama_barang', 'kategori', 'jumlah_total', 'jumlah_rusak', 'di_sewa', 'harga_rental']);
    }

    public function test_deleting_master_barang_removes_stored_photo(): void
    {
        Storage::fake('public');
        Sanctum::actingAs(User::factory()->create());
        $path = 'barang/to-delete.jpg';
        Storage::disk('public')->put($path, 'dummy');
        $barang = MasterBarang::query()->create([
            'kode_barang' => 'BRG-DEL-PHOTO',
            'nama_barang' => 'Barang Hapus Foto',
            'kategori' => 'Audio',
            'satuan' => 'unit',
            'jumlah_total' => 1,
            'jumlah_rusak' => 0,
            'di_sewa' => 0,
            'harga_rental' => 1000,
            'foto_path' => $path,
        ]);

        $this->deleteJson('/api/v1/master-barang/'.$barang->id)->assertNoContent();

        $this->assertDatabaseMissing('master_barangs', ['id' => $barang->id]);
        Storage::disk('public')->assertMissing($path);
    }
}
