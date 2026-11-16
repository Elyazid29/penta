<?php

namespace Tests\Feature;

use App\Models\DeliveryPhoto;
use App\Models\FormOrder;
use App\Models\MasterKaryawan;
use App\Models\MasterKlien;
use App\Models\Role;
use App\Models\SuratJalan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuratJalanAndDeliveryFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_surat_jalan_and_items_are_copied_from_form_order(): void
    {
        Sanctum::actingAs($this->userWithRole('admin'));
        $order = $this->makeOrderWithItems();

        $response = $this->postJson('/api/v1/surat-jalan', [
            'nomor_surat_jalan' => 'SJ-AUTO-001',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => '2026-11-15 08:00:00',
            'status' => 'Draft',
        ]);

        $response->assertCreated();
        $suratJalan = SuratJalan::query()->where('nomor_surat_jalan', 'SJ-AUTO-001')->firstOrFail();
        $this->assertDatabaseHas('surat_jalan_items', [
            'surat_jalan_id' => $suratJalan->id,
            'nama_item' => 'Speaker Aktif',
            'qty' => 2,
        ]);
    }

    public function test_checkout_denies_unassigned_teknisi_but_allows_admin_and_stores_photo(): void
    {
        Storage::fake('public');

        $assignedUser = $this->userWithRole('teknisi');
        $otherUser = $this->userWithRole('teknisi');
        $admin = $this->userWithRole('admin');

        $karyawan = MasterKaryawan::query()->create([
            'nama_lengkap' => 'Teknisi Ditugaskan',
            'jabatan' => 'Teknisi',
            'status' => 'Aktif',
            'user_id' => $assignedUser->id,
        ]);

        $order = $this->makeOrderWithItems();
        $suratJalan = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-CHECK-001',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => now(),
            'status' => 'Draft',
        ]);
        $suratJalan->items()->create(['nama_item' => 'Speaker Aktif', 'qty' => 2, 'status_check' => 'Belum Check-out']);
        $suratJalan->teknisi()->attach($karyawan->id);

        Sanctum::actingAs($otherUser);
        $this->post('/api/v1/delivery/checkout', [
            'surat_jalan_id' => $suratJalan->id,
            'foto' => UploadedFile::fake()->image('bukti.jpg', 800, 600),
            'items' => [[
                'nama_barang' => 'Speaker Aktif',
                'qty' => 2,
                'status_fisik' => 'Baik',
            ]],
        ], ['Accept' => 'application/json'])
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak ditugaskan pada Surat Jalan ini.');

        Sanctum::actingAs($admin);
        $response = $this->post('/api/v1/delivery/checkout', [
            'surat_jalan_id' => $suratJalan->id,
            'foto' => UploadedFile::fake()->image('bukti-admin.jpg', 800, 600),
            'items' => [[
                'nama_barang' => 'Speaker Aktif',
                'qty' => 2,
                'status_fisik' => 'Baik',
                'keterangan' => 'Lengkap',
            ]],
            'catatan' => 'Checkout oleh admin',
        ], ['Accept' => 'application/json']);

        $response->assertCreated();
        $photoId = $response->json('data.id');
        $this->assertNotNull($photoId);

        $photo = DeliveryPhoto::query()->findOrFail($photoId);
        $this->assertSame('checkout', $photo->tipe);
        $this->assertNotNull($photo->foto_path);
        Storage::disk('public')->assertExists($photo->foto_path);
        if ($photo->foto_thumb_path) {
            Storage::disk('public')->assertExists($photo->foto_thumb_path);
        }
        $this->assertDatabaseHas('delivery_item_checklists', [
            'delivery_photo_id' => $photo->id,
            'nama_barang' => 'Speaker Aktif',
            'status_fisik' => 'Baik',
        ]);
    }

    public function test_checkin_stores_photo_and_checklists(): void
    {
        Storage::fake('public');
        Sanctum::actingAs($this->userWithRole('admin'));

        $order = $this->makeOrderWithItems();
        $suratJalan = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-CHECKIN-001',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => now(),
            'status' => 'Draft',
        ]);
        $suratJalan->items()->create(['nama_item' => 'Speaker Aktif', 'qty' => 2, 'status_check' => 'Belum Check-out']);

        $response = $this->post('/api/v1/delivery/checkin', [
            'surat_jalan_id' => $suratJalan->id,
            'foto' => UploadedFile::fake()->image('kembali.jpg', 800, 600),
            'items' => [[
                'nama_barang' => 'Speaker Aktif',
                'qty' => 2,
                'status_fisik' => 'Baik',
                'keterangan' => 'Kembali lengkap',
            ]],
            'catatan' => 'Selesai',
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('data.tipe', 'checkin');
        $photoId = $response->json('data.id');
        $photo = DeliveryPhoto::query()->findOrFail($photoId);
        Storage::disk('public')->assertExists($photo->foto_path);
        $this->assertDatabaseHas('delivery_item_checklists', [
            'delivery_photo_id' => $photo->id,
            'nama_barang' => 'Speaker Aktif',
            'keterangan' => 'Kembali lengkap',
        ]);
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function makeOrderWithItems(): FormOrder
    {
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Flow']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-FLOW-'.uniqid(),
            'klien_id' => $klien->id,
            'nama_event' => 'Event Flow',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
        $order->items()->create([
            'nama_item' => 'Speaker Aktif',
            'qty' => 2,
            'harga_harian' => 150000,
        ]);

        return $order;
    }
}
