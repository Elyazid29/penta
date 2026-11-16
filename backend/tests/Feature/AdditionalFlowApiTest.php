<?php

namespace Tests\Feature;

use App\Models\DeliveryAdditionalItem;
use App\Models\FormOrder;
use App\Models\FormPenawaran;
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

class AdditionalFlowApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_scan_surat_jalan_returns_not_found_or_payload_with_assignment_flag(): void
    {
        $teknisi = $this->actingAsRole('teknisi');

        $this->postJson('/api/v1/delivery/scan-surat-jalan', [
            'kode_surat_jalan' => 'TIDAK-ADA',
        ])->assertNotFound()->assertJsonPath('message', 'Surat Jalan tidak ditemukan.');

        $order = $this->makeOrder('ORD-SCAN-001');
        $openSj = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-OPEN-001',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => now(),
            'status' => 'Draft',
        ]);

        $this->postJson('/api/v1/delivery/scan-surat-jalan', [
            'kode_surat_jalan' => 'SJ-OPEN-001',
        ])->assertOk()
            ->assertJsonPath('data.nomor_surat_jalan', 'SJ-OPEN-001')
            ->assertJsonPath('data.is_assigned', true);

        $assignedUser = $this->actingAsRole('teknisi');
        $karyawan = MasterKaryawan::query()->create([
            'nama_lengkap' => 'Teknisi Assigned',
            'jabatan' => 'Teknisi',
            'status' => 'Aktif',
            'user_id' => $assignedUser->id,
        ]);
        $assignedSj = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-ASSIGNED-001',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => now(),
            'status' => 'Draft',
        ]);
        $assignedSj->teknisi()->attach($karyawan->id);

        Sanctum::actingAs($teknisi);
        $this->postJson('/api/v1/delivery/scan-surat-jalan', [
            'kode_surat_jalan' => 'SJ-ASSIGNED-001',
        ])->assertOk()
            ->assertJsonPath('data.nomor_surat_jalan', 'SJ-ASSIGNED-001')
            ->assertJsonPath('data.is_assigned', false);
    }

    public function test_delivery_additional_item_can_be_stored_and_listed(): void
    {
        Storage::fake('public');
        $this->actingAsRole('teknisi');
        $order = $this->makeOrder('ORD-ADD-001');
        $suratJalan = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-ADD-001',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => now(),
            'status' => 'Draft',
        ]);

        $response = $this->post('/api/v1/delivery-additional-items', [
            'surat_jalan_id' => $suratJalan->id,
            'nama_barang' => 'Kabel HDMI',
            'qty' => 2,
            'alasan' => 'Kebutuhan mendadak',
            'foto' => UploadedFile::fake()->image('tambahan.jpg', 600, 400),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('data.nama_barang', 'Kabel HDMI');
        $item = DeliveryAdditionalItem::query()->firstOrFail();
        $this->assertNotNull($item->foto_path);
        Storage::disk('public')->assertExists($item->foto_path);

        $this->getJson("/api/v1/delivery-additional-items/{$suratJalan->id}")
            ->assertOk()
            ->assertJsonPath('data.data.0.nama_barang', 'Kabel HDMI');
    }

    public function test_form_penawaran_calculates_subtotal_and_total(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $order = $this->makeOrder('ORD-PEN-001');

        $response = $this->postJson('/api/v1/form-penawaran', [
            'nomor_penawaran' => 'PEN-001',
            'form_orders_id' => $order->id,
            'tanggal_penawaran' => '2026-11-16',
            'status' => 'Draft',
            'diskon' => 50000,
            'pajak' => 10000,
            'items' => [
                ['nama_item' => 'Item A', 'qty' => 2, 'harga' => 100000],
                ['nama_item' => 'Item B', 'qty' => 1, 'harga' => 50000],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.subtotal', '250000.00')
            ->assertJsonPath('data.total', '210000.00');

        $this->assertDatabaseHas('form_penawaran_items', [
            'nama_item' => 'Item A',
            'qty' => 2,
            'subtotal' => '200000.00',
        ]);
        $this->assertSame(1, FormPenawaran::query()->count());
    }

    private function actingAsRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function makeOrder(string $nomor): FormOrder
    {
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Flow '.uniqid()]);

        return FormOrder::query()->create([
            'nomor_order' => $nomor,
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
    }
}
