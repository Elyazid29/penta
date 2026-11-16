<?php

namespace Tests\Feature;

use App\Models\FormOrder;
use App\Models\MasterKlien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FormOrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_update_and_replace_form_order_items(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Order']);

        $created = $this->postJson('/api/v1/form-order', [
            'nomor_order' => 'ORD-CRUD-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event Awal',
            'venue' => 'Jakarta',
            'load_in' => '2026-11-20 08:00:00',
            'load_out' => '2026-11-21 18:00:00',
            'status' => 'Draft',
            'items' => [
                ['nama_item' => 'Speaker', 'qty' => 2, 'harga_harian' => 150000],
                ['nama_item' => 'Mic', 'qty' => 1, 'harga_harian' => 50000],
            ],
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.nomor_order', 'ORD-CRUD-001')
            ->assertJsonCount(2, 'data.items');

        $orderId = $created->json('data.id');
        $this->assertDatabaseHas('form_order_items', ['form_order_id' => $orderId, 'nama_item' => 'Speaker']);

        $updated = $this->putJson("/api/v1/form-order/{$orderId}", [
            'nomor_order' => 'ORD-CRUD-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event Update',
            'venue' => 'Bandung',
            'load_in' => '2026-11-20 08:00:00',
            'load_out' => '2026-11-21 18:00:00',
            'status' => 'Confirmed',
            'items' => [
                ['nama_item' => 'LED Panel', 'qty' => 3, 'harga_harian' => 300000],
            ],
        ]);

        $updated->assertOk()
            ->assertJsonPath('data.nama_event', 'Event Update')
            ->assertJsonPath('data.status', 'Confirmed')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.nama_item', 'LED Panel');

        $this->assertDatabaseMissing('form_order_items', ['form_order_id' => $orderId, 'nama_item' => 'Speaker']);
        $this->assertDatabaseHas('form_order_items', ['form_order_id' => $orderId, 'nama_item' => 'LED Panel']);

        $this->assertSame(1, FormOrder::query()->count());
    }

    public function test_form_order_validation_rejects_invalid_payload(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Validasi']);

        $this->postJson('/api/v1/form-order', [
            'nomor_order' => 'ORD-BAD',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => '2026-11-21 18:00:00',
            'load_out' => '2026-11-20 08:00:00',
            'status' => 'Draft',
            'items' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['load_out', 'items']);

        $this->postJson('/api/v1/form-order', [
            'nomor_order' => 'ORD-BAD-2',
            'klien_id' => 999999,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => '2026-11-20 08:00:00',
            'load_out' => '2026-11-21 18:00:00',
            'status' => 'Unknown',
            'items' => [['nama_item' => 'X', 'qty' => 0, 'harga_harian' => -1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['klien_id', 'status', 'items.0.qty', 'items.0.harga_harian']);
    }

    public function test_form_order_can_be_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Hapus']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-DEL-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => '2026-11-20 08:00:00',
            'load_out' => '2026-11-21 18:00:00',
            'status' => 'Draft',
        ]);

        $this->deleteJson('/api/v1/form-order/'.$order->id)->assertNoContent();
        $this->assertDatabaseMissing('form_orders', ['id' => $order->id]);
    }
}
