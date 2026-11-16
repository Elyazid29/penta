<?php

namespace Tests\Feature;

use App\Models\FormOrder;
use App\Models\Invoice;
use App\Models\MasterKlien;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_index_refreshes_overdue_status(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Inv']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-INV-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);

        $invoice = Invoice::query()->create([
            'nomor_invoice' => 'INV-REFRESH-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-10-01',
            'jatuh_tempo' => now()->subDay()->toDateString(),
            'subtotal' => 500000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 500000,
            'dibayar' => 0,
            'status' => 'Unpaid',
        ]);

        $this->getJson('/api/v1/invoice')->assertOk();
        $invoice->refresh();
        $this->assertSame('Overdue', $invoice->status);
    }

    public function test_invoice_can_be_updated_and_deleted(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Inv 2']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-INV-002',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
        $invoice = Invoice::query()->create([
            'nomor_invoice' => 'INV-UPD-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-01',
            'subtotal' => 1000000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1000000,
            'dibayar' => 0,
            'status' => 'Unpaid',
        ]);

        $this->putJson('/api/v1/invoice/'.$invoice->id, [
            'nomor_invoice' => 'INV-UPD-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-01',
            'subtotal' => 1200000,
            'diskon' => 100000,
            'pajak' => 50000,
            'total' => 1150000,
            'dibayar' => 200000,
            'status' => 'DP',
            'catatan' => 'Update',
        ])->assertOk()
            ->assertJsonPath('data.total', '1150000.00')
            ->assertJsonPath('data.status', 'DP');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'total' => '1150000.00', 'status' => 'DP']);

        $this->deleteJson('/api/v1/invoice/'.$invoice->id)->assertNoContent();
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }
}
