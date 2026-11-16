<?php

namespace Tests\Feature;

use App\Models\FormOrder;
use App\Models\Invoice;
use App\Models\MasterKlien;
use App\Models\TransaksiKeuangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LaporanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_summary_includes_cashflow_and_receivables(): void
    {
        Sanctum::actingAs(User::factory()->create());

        TransaksiKeuangan::query()->create([
            'tanggal' => '2026-11-10',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 1000000,
        ]);
        TransaksiKeuangan::query()->create([
            'tanggal' => '2026-11-11',
            'jenis' => 'Pengeluaran',
            'kategori' => 'Operasional',
            'jumlah' => 400000,
        ]);

        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Laporan']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-LAP-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);

        Invoice::query()->create([
            'nomor_invoice' => 'INV-LAP-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-10',
            'subtotal' => 1000000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1000000,
            'dibayar' => 250000,
            'status' => 'DP',
        ]);
        Invoice::query()->create([
            'nomor_invoice' => 'INV-LAP-002',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-10-01',
            'jatuh_tempo' => now()->subDay()->toDateString(),
            'subtotal' => 500000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 500000,
            'dibayar' => 0,
            'status' => 'Overdue',
        ]);

        $response = $this->getJson('/api/v1/laporan/summary');

        $response->assertOk()
            ->assertJsonPath('data.pemasukan', 1000000)
            ->assertJsonPath('data.pengeluaran', 400000)
            ->assertJsonPath('data.saldo', 600000)
            ->assertJsonPath('data.laba_rugi', 600000)
            ->assertJsonPath('data.piutang', 1250000)
            ->assertJsonPath('data.invoice_overdue', 1)
            ->assertJsonPath('data.pemasukan_per_kategori.0.kategori', 'DP')
            ->assertJsonPath('data.pemasukan_per_kategori.0.total', 1000000)
            ->assertJsonPath('data.pengeluaran_per_kategori.0.kategori', 'Operasional')
            ->assertJsonPath('data.pengeluaran_per_kategori.0.total', 400000);
    }

    public function test_laporan_invoices_csv_can_be_downloaded(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT CSV']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-CSV-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
        Invoice::query()->create([
            'nomor_invoice' => 'INV-CSV-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-10',
            'subtotal' => 1000000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1000000,
            'dibayar' => 250000,
            'status' => 'DP',
        ]);

        $response = $this->get('/api/v1/laporan/invoices.csv');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Nomor Invoice', $response->streamedContent());
        $this->assertStringContainsString('INV-CSV-001', $response->streamedContent());
    }
}
