<?php

namespace Tests\Feature;

use App\Models\FormOrder;
use App\Models\Invoice;
use App\Models\JadwalOperasional;
use App\Models\MasterArmada;
use App\Models\MasterKaryawan;
use App\Models\MasterKlien;
use App\Models\Role;
use App\Models\TransaksiKeuangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FinanceAndScheduleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaksi_keuangan_index_returns_summary_totals(): void
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
            'jumlah' => 250000,
        ]);

        $response = $this->getJson('/api/v1/transaksi-keuangan');

        $response->assertOk()
            ->assertJsonPath('summary.pemasukan', 1000000)
            ->assertJsonPath('summary.pengeluaran', 250000)
            ->assertJsonPath('summary.saldo', 750000);
    }

    public function test_pemasukan_transaction_syncs_invoice_dibayar_and_status(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $order = $this->makeOrder();
        $invoice = Invoice::query()->create([
            'nomor_invoice' => 'INV-SYNC-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-15',
            'subtotal' => 1000000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1000000,
            'dibayar' => 0,
            'status' => 'Unpaid',
        ]);

        $this->postJson('/api/v1/transaksi-keuangan', [
            'invoice_id' => $invoice->id,
            'tanggal' => '2026-11-16',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 250000,
        ])->assertCreated();

        $invoice->refresh();
        $this->assertSame('250000.00', (string) $invoice->dibayar);
        $this->assertSame('DP', $invoice->status);

        $this->postJson('/api/v1/transaksi-keuangan', [
            'invoice_id' => $invoice->id,
            'tanggal' => '2026-11-17',
            'jenis' => 'Pemasukan',
            'kategori' => 'Pelunasan',
            'jumlah' => 750000,
        ])->assertCreated();

        $invoice->refresh();
        $this->assertSame('1000000.00', (string) $invoice->dibayar);
        $this->assertSame('Paid', $invoice->status);
    }

    public function test_invoice_becomes_overdue_when_past_due_and_not_fully_paid(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $order = $this->makeOrder();
        $invoice = Invoice::query()->create([
            'nomor_invoice' => 'INV-OVERDUE-001',
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

        $this->postJson('/api/v1/transaksi-keuangan', [
            'invoice_id' => $invoice->id,
            'tanggal' => '2026-10-02',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 100000,
        ])->assertCreated();

        $invoice->refresh();
        $this->assertSame('Overdue', $invoice->status);
        $this->assertSame('100000.00', (string) $invoice->dibayar);
    }

    public function test_invoice_can_be_created_with_correct_fields_and_legacy_aliases(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $order = $this->makeOrder();

        $response = $this->postJson('/api/v1/invoice', [
            'nomor_invoices' => 'INV-LEGACY-001',
            'form_orders_id' => $order->id,
            'tanggal_invoices' => '2026-11-12',
            'subtotal' => 1000000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1000000,
            'dibayar' => 250000,
            'status' => 'DP',
        ]);

        $response->assertCreated()->assertJsonPath('data.nomor_invoice', 'INV-LEGACY-001');
        $this->assertDatabaseHas('invoices', [
            'nomor_invoice' => 'INV-LEGACY-001',
            'form_order_id' => $order->id,
            'status' => 'DP',
        ]);
        $this->assertSame(1, Invoice::query()->count());
    }

    public function test_jadwal_operasional_accepts_form_order_id_and_legacy_alias(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        Sanctum::actingAs(User::factory()->create(['role_id' => $adminRole->id]));
        $order = $this->makeOrder();

        $response = $this->postJson('/api/v1/jadwal-operasional', [
            'form_orders_id' => $order->id,
            'tanggal_mulai' => '2026-11-13 08:00:00',
            'tanggal_selesai' => '2026-11-13 17:00:00',
            'jobdesk' => 'Setup sound',
            'status' => 'Terjadwal',
        ]);

        $response->assertCreated()->assertJsonPath('data.form_order_id', $order->id);
        $this->assertDatabaseHas('jadwal_operasionals', [
            'form_order_id' => $order->id,
            'jobdesk' => 'Setup sound',
            'status' => 'Terjadwal',
        ]);
        $this->assertSame(1, JadwalOperasional::query()->count());
    }

    public function test_jadwal_operasional_rejects_conflicting_karyawan_or_armada(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        Sanctum::actingAs(User::factory()->create(['role_id' => $adminRole->id]));
        $order = $this->makeOrder();
        $karyawan = MasterKaryawan::query()->create(['nama_lengkap' => 'Teknisi A', 'status' => 'Aktif']);
        $armada = MasterArmada::query()->create([
            'jenis_kendaraan' => 'Mobil Box',
            'plat_nomor' => 'B '.uniqid().' XX',
            'status_stnk' => 'Aktif',
            'status_operasional' => 'Standby',
        ]);

        $this->postJson('/api/v1/jadwal-operasional', [
            'form_order_id' => $order->id,
            'karyawan_id' => $karyawan->id,
            'armada_id' => $armada->id,
            'tanggal_mulai' => '2026-11-14 08:00:00',
            'tanggal_selesai' => '2026-11-14 12:00:00',
            'status' => 'Terjadwal',
        ])->assertCreated();

        $this->postJson('/api/v1/jadwal-operasional', [
            'form_order_id' => $order->id,
            'karyawan_id' => $karyawan->id,
            'tanggal_mulai' => '2026-11-14 10:00:00',
            'tanggal_selesai' => '2026-11-14 14:00:00',
            'status' => 'Terjadwal',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Jadwal bentrok: karyawan sudah ditugaskan pada rentang waktu tersebut.');

        $this->postJson('/api/v1/jadwal-operasional', [
            'form_order_id' => $order->id,
            'armada_id' => $armada->id,
            'tanggal_mulai' => '2026-11-14 10:00:00',
            'tanggal_selesai' => '2026-11-14 14:00:00',
            'status' => 'Terjadwal',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'Jadwal bentrok: armada sudah digunakan pada rentang waktu tersebut.');
    }

    public function test_jadwal_operasional_index_supports_filters(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        Sanctum::actingAs(User::factory()->create(['role_id' => $adminRole->id]));
        $order = $this->makeOrder();
        $karyawan = MasterKaryawan::query()->create(['nama_lengkap' => 'Teknisi B', 'status' => 'Aktif']);

        JadwalOperasional::query()->create([
            'form_order_id' => $order->id,
            'karyawan_id' => $karyawan->id,
            'tanggal_mulai' => '2026-11-20 08:00:00',
            'tanggal_selesai' => '2026-11-20 10:00:00',
            'status' => 'Terjadwal',
        ]);
        JadwalOperasional::query()->create([
            'form_order_id' => $order->id,
            'tanggal_mulai' => '2026-11-25 08:00:00',
            'tanggal_selesai' => '2026-11-25 10:00:00',
            'status' => 'Selesai',
        ]);

        $this->getJson('/api/v1/jadwal-operasional?karyawan_id='.$karyawan->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->getJson('/api/v1/jadwal-operasional?status=Selesai')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');

        $this->getJson('/api/v1/jadwal-operasional?from=2026-11-19&to=2026-11-21')
            ->assertOk()
            ->assertJsonCount(1, 'data.data');
    }

    private function makeOrder(): FormOrder
    {
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Uji']);

        return FormOrder::query()->create([
            'nomor_order' => 'ORD-TEST-'.uniqid(),
            'klien_id' => $klien->id,
            'nama_event' => 'Event Uji',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
    }
}
