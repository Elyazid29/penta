<?php

namespace Tests\Feature;

use App\Models\FormOrder;
use App\Models\Invoice;
use App\Models\JadwalOperasional;
use App\Models\MasterBarang;
use App\Models\MasterKlien;
use App\Models\Role;
use App\Models\SuratJalan;
use App\Models\TransaksiKeuangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LaporanApiTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): void
    {
        $role = Role::query()->where('name', 'admin')->firstOrFail();
        Sanctum::actingAs(User::factory()->create(['role_id' => $role->id]));
    }

    public function test_laporan_summary_includes_cashflow_and_receivables(): void
    {
        $this->actingAsAdmin();

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
        $this->actingAsAdmin();
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
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Nomor Invoice', $content);
        $this->assertStringContainsString('INV-CSV-001', $content);
    }

    public function test_laporan_invoices_csv_empty_still_returns_header(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/api/v1/laporan/invoices.csv');
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Nomor Invoice', $content);
    }

    public function test_laporan_summary_includes_per_event_profit_and_loss(): void
    {
        $this->actingAsAdmin();
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Event']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-EVT-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Konser',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);

        TransaksiKeuangan::query()->create([
            'form_order_id' => $order->id,
            'tanggal' => '2026-11-10',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 1000000,
        ]);
        TransaksiKeuangan::query()->create([
            'form_order_id' => $order->id,
            'tanggal' => '2026-11-11',
            'jenis' => 'Pengeluaran',
            'kategori' => 'Biaya Event',
            'jumlah' => 300000,
        ]);

        $this->getJson('/api/v1/laporan/summary')
            ->assertOk()
            ->assertJsonPath('data.per_event.0.form_order_id', $order->id)
            ->assertJsonPath('data.per_event.0.nomor_order', 'ORD-EVT-001')
            ->assertJsonPath('data.per_event.0.nama_event', 'Konser')
            ->assertJsonPath('data.per_event.0.pemasukan', 1000000)
            ->assertJsonPath('data.per_event.0.pengeluaran', 300000)
            ->assertJsonPath('data.per_event.0.laba_rugi', 700000);
    }

    public function test_laporan_operasional_csv_can_be_downloaded(): void
    {
        $this->actingAsAdmin();
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Ops']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-OPS-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event Ops',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
        SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-OPS-001',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => now(),
            'status' => 'Draft',
        ]);
        JadwalOperasional::query()->create([
            'form_order_id' => $order->id,
            'tanggal_mulai' => '2026-11-20 08:00:00',
            'tanggal_selesai' => '2026-11-20 17:00:00',
            'status' => 'Terjadwal',
            'jobdesk' => 'Setup',
        ]);

        $sj = $this->get('/api/v1/laporan/surat-jalan.csv');
        $sj->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $sjContent = $sj->streamedContent();
        $this->assertStringContainsString('Nomor Surat Jalan', $sjContent);
        $this->assertStringContainsString('SJ-OPS-001', $sjContent);

        $jadwal = $this->get('/api/v1/laporan/jadwal-operasional.csv');
        $jadwal->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $jadwal->streamedContent();
        $this->assertStringContainsString('Tanggal Mulai', $content);
        $this->assertStringContainsString('ORD-OPS-001', $content);
    }

    public function test_teknisi_cannot_access_laporan_endpoints(): void
    {
        $role = Role::query()->where('name', 'teknisi')->firstOrFail();
        Sanctum::actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->getJson('/api/v1/laporan/summary')->assertForbidden();
        $this->get('/api/v1/laporan/transactions.csv')->assertForbidden();
        $this->get('/api/v1/laporan/invoices.csv')->assertForbidden();
        $this->get('/api/v1/laporan/surat-jalan.csv')->assertForbidden();
        $this->get('/api/v1/laporan/jadwal-operasional.csv')->assertForbidden();
    }

    public function test_owner_can_access_laporan_endpoints(): void
    {
        $role = Role::query()->where('name', 'owner')->firstOrFail();
        Sanctum::actingAs(User::factory()->create(['role_id' => $role->id]));

        $this->getJson('/api/v1/laporan/summary')->assertOk()->assertJsonStructure(['data']);
        $this->get('/api/v1/laporan/transactions.csv')->assertOk();
        $this->get('/api/v1/laporan/invoices.csv')->assertOk();
        $this->get('/api/v1/laporan/surat-jalan.csv')->assertOk();
        $this->get('/api/v1/laporan/jadwal-operasional.csv')->assertOk();
    }

    public function test_laporan_transactions_csv_contains_order_id_and_bom(): void
    {
        $this->actingAsAdmin();
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT CSV2']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-CSV-TRX',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
        TransaksiKeuangan::query()->create([
            'form_order_id' => $order->id,
            'tanggal' => '2026-11-10',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 500000,
        ]);

        $response = $this->get('/api/v1/laporan/transactions.csv');
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Order ID', $content);
        $this->assertStringContainsString((string) $order->id, $content);
    }

    public function test_laporan_transactions_csv_empty_still_returns_header(): void
    {
        $this->actingAsAdmin();

        $response = $this->get('/api/v1/laporan/transactions.csv');
        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $content);
        $this->assertStringContainsString('Tanggal', $content);
    }

    public function test_laporan_operasional_csv_empty_still_returns_headers(): void
    {
        $this->actingAsAdmin();

        $sj = $this->get('/api/v1/laporan/surat-jalan.csv');
        $sj->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $sjContent = $sj->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $sjContent);
        $this->assertStringContainsString('Nomor Surat Jalan', $sjContent);

        $jadwal = $this->get('/api/v1/laporan/jadwal-operasional.csv');
        $jadwal->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $jadwalContent = $jadwal->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $jadwalContent);
        $this->assertStringContainsString('Tanggal Mulai', $jadwalContent);
    }

    public function test_laporan_summary_empty_returns_default_values(): void
    {
        $this->actingAsAdmin();

        $this->getJson('/api/v1/laporan/summary')
            ->assertOk()
            ->assertJsonPath('data.orders', 0)
            ->assertJsonPath('data.invoices', 0)
            ->assertJsonPath('data.barang', 0)
            ->assertJsonPath('data.pemasukan', 0)
            ->assertJsonPath('data.pengeluaran', 0)
            ->assertJsonPath('data.saldo', 0)
            ->assertJsonPath('data.laba_rugi', 0)
            ->assertJsonPath('data.piutang', 0)
            ->assertJsonPath('data.invoice_overdue', 0)
            ->assertJsonPath('data.per_event', [])
            ->assertJsonPath('data.pemasukan_per_kategori', [])
            ->assertJsonPath('data.pengeluaran_per_kategori', []);
    }

    public function test_laporan_summary_counts_operational_entities(): void
    {
        $this->actingAsAdmin();
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Count']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-CNT-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
        MasterBarang::query()->create([
            'kode_barang' => 'BRG-CNT-001',
            'nama_barang' => 'Kamera',
            'kategori' => 'Kamera',
            'satuan' => 'unit',
            'harga_sewa' => 100000,
            'stok_total' => 1,
        ]);
        Invoice::query()->create([
            'nomor_invoice' => 'INV-CNT-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-10',
            'subtotal' => 100000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 100000,
            'dibayar' => 0,
            'status' => 'Unpaid',
        ]);

        $this->getJson('/api/v1/laporan/summary')
            ->assertOk()
            ->assertJsonPath('data.orders', 1)
            ->assertJsonPath('data.barang', 1)
            ->assertJsonPath('data.invoices', 1);
    }
}
