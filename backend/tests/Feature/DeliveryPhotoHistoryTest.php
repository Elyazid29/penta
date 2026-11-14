<?php

namespace Tests\Feature;

use App\Models\DeliveryPhoto;
use App\Models\FormOrder;
use App\Models\MasterKlien;
use App\Models\Role;
use App\Models\SuratJalan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeliveryPhotoHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_photo_history_returns_thumbnail_url_and_checklists(): void
    {
        Storage::fake('public');
        $teknisiRole = Role::query()->where('name', 'teknisi')->firstOrFail();
        $user = User::factory()->create(['role_id' => $teknisiRole->id]);
        Sanctum::actingAs($user);

        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Contoh']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Upacara',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
        ]);
        $suratJalan = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-001',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => now(),
        ]);

        $photo = DeliveryPhoto::query()->create([
            'surat_jalan_id' => $suratJalan->id,
            'user_id' => $user->id,
            'tipe' => 'checkout',
            'foto_path' => 'delivery-photos/checkout/a.jpg',
            'foto_thumb_path' => 'delivery-photos/checkout/thumbs/a.jpg',
            'catatan' => 'Berangkat tepat waktu',
        ]);
        $photo->checklists()->create([
            'nama_barang' => 'Kamera',
            'qty' => 2,
            'status_fisik' => 'Baik',
        ]);

        $response = $this->getJson("/api/v1/surat-jalan/{$suratJalan->id}/photos");

        $response->assertOk()
            ->assertJsonPath('data.0.tipe', 'checkout')
            ->assertJsonPath('data.0.foto_thumb_url', Storage::disk('public')->url('delivery-photos/checkout/thumbs/a.jpg'))
            ->assertJsonPath('data.0.checklists.0.nama_barang', 'Kamera');
    }
}
