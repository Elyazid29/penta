<?php

namespace Tests\Feature;

use App\Models\FormOrder;
use App\Models\Invoice;
use App\Models\JadwalOperasional;
use App\Models\MasterArmada;
use App\Models\MasterBarang;
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

        $response = $this->postJson('/api/v1/transaksi-keuangan', [
            'invoice_id' => $invoice->id,
            'tanggal' => '2026-11-16',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 250000,
        ])->assertCreated()
            ->assertJsonPath('data.form_order_id', $order->id);

        $this->assertDatabaseHas('transaksi_keuangans', [
            'id' => $response->json('data.id'),
            'invoice_id' => $invoice->id,
            'form_order_id' => $order->id,
        ]);

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

    public function test_jadwal_operasional_can_assign_alats(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        Sanctum::actingAs(User::factory()->create(['role_id' => $adminRole->id]));
        $order = $this->makeOrder();
        $barang = MasterBarang::query()->create([
            'kode_barang' => 'BRG-JAD-001',
            'nama_barang' => 'Speaker',
            'kategori' => 'Sound System',
            'satuan' => 'unit',
            'jumlah_total' => 10,
            'jumlah_rusak' => 0,
            'di_sewa' => 0,
            'harga_rental' => 100000,
        ]);

        $response = $this->postJson('/api/v1/jadwal-operasional', [
            'form_order_id' => $order->id,
            'tanggal_mulai' => '2026-11-26 08:00:00',
            'tanggal_selesai' => '2026-11-26 17:00:00',
            'status' => 'Terjadwal',
            'alats' => [
                ['barang_id' => $barang->id, 'qty' => 2],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonCount(1, 'data.alats')
            ->assertJsonPath('data.alats.0.id', $barang->id)
            ->assertJsonPath('data.alats.0.pivot.qty', 2);

        $this->assertDatabaseHas('jadwal_operasional_alats', [
            'master_barang_id' => $barang->id,
            'qty' => 2,
        ]);
    }

    public function test_jadwal_operasional_can_update_and_clear_alats(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        Sanctum::actingAs(User::factory()->create(['role_id' => $adminRole->id]));
        $order = $this->makeOrder();
        $barangA = MasterBarang::query()->create([
            'kode_barang' => 'BRG-JAD-A',
            'nama_barang' => 'Mic',
            'kategori' => 'Audio',
            'satuan' => 'unit',
            'jumlah_total' => 5,
            'jumlah_rusak' => 0,
            'di_sewa' => 0,
            'harga_rental' => 50000,
        ]);
        $barangB = MasterBarang::query()->create([
            'kode_barang' => 'BRG-JAD-B',
            'nama_barang' => 'Mixer',
            'kategori' => 'Audio',
            'satuan' => 'unit',
            'jumlah_total' => 3,
            'jumlah_rusak' => 0,
            'di_sewa' => 0,
            'harga_rental' => 150000,
        ]);

        $jadwal = JadwalOperasional::query()->create([
            'form_order_id' => $order->id,
            'tanggal_mulai' => '2026-11-27 08:00:00',
            'tanggal_selesai' => '2026-11-27 17:00:00',
            'status' => 'Terjadwal',
        ]);
        $jadwal->alats()->sync([$barangA->id => ['qty' => 1]]);

        $this->putJson('/api/v1/jadwal-operasional/'.$jadwal->id, [
            'form_order_id' => $order->id,
            'tanggal_mulai' => '2026-11-27 08:00:00',
            'tanggal_selesai' => '2026-11-27 17:00:00',
            'status' => 'Terjadwal',
            'alats' => [
                ['barang_id' => $barangB->id, 'qty' => 3],
            ],
        ])->assertOk()
            ->assertJsonCount(1, 'data.alats')
            ->assertJsonPath('data.alats.0.id', $barangB->id)
            ->assertJsonPath('data.alats.0.pivot.qty', 3);

        $this->assertDatabaseMissing('jadwal_operasional_alats', ['jadwal_operasional_id' => $jadwal->id, 'master_barang_id' => $barangA->id]);
        $this->assertDatabaseHas('jadwal_operasional_alats', ['jadwal_operasional_id' => $jadwal->id, 'master_barang_id' => $barangB->id, 'qty' => 3]);

        $this->putJson('/api/v1/jadwal-operasional/'.$jadwal->id, [
            'form_order_id' => $order->id,
            'tanggal_mulai' => '2026-11-27 08:00:00',
            'tanggal_selesai' => '2026-11-27 17:00:00',
            'status' => 'Terjadwal',
            'alats' => [],
        ])->assertOk()->assertJsonCount(0, 'data.alats');

        $this->assertDatabaseCount('jadwal_operasional_alats', 0);
    }

    public function test_jadwal_operasional_rejects_invalid_alat(): void
    {
        $adminRole = Role::query()->where('name', 'admin')->firstOrFail();
        Sanctum::actingAs(User::factory()->create(['role_id' => $adminRole->id]));
        $order = $this->makeOrder();

        $this->postJson('/api/v1/jadwal-operasional', [
            'form_order_id' => $order->id,
            'tanggal_mulai' => '2026-11-28 08:00:00',
            'tanggal_selesai' => '2026-11-28 17:00:00',
            'status' => 'Terjadwal',
            'alats' => [
                ['barang_id' => 999999, 'qty' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['alats.0.barang_id']);
    }

    public function test_invoice_validation_rejects_dibayar_exceeding_total_and_invalid_status(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $order = $this->makeOrder();

        $this->postJson('/api/v1/invoice', [
            'nomor_invoice' => 'INV-BAD-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-12',
            'subtotal' => 1000000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1000000,
            'dibayar' => 1500000,
            'status' => 'DP',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['dibayar']);

        $this->postJson('/api/v1/invoice', [
            'nomor_invoice' => 'INV-BAD-002',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-12',
            'subtotal' => 1000000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1000000,
            'dibayar' => 0,
            'status' => 'Invalid',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_invoice_rejects_duplicate_nomor(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $order = $this->makeOrder();
        Invoice::query()->create([
            'nomor_invoice' => 'INV-DUP-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-12',
            'subtotal' => 1000000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1000000,
            'dibayar' => 0,
            'status' => 'Unpaid',
        ]);

        $this->postJson('/api/v1/invoice', [
            'nomor_invoice' => 'INV-DUP-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-13',
            'subtotal' => 500000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 500000,
            'dibayar' => 0,
            'status' => 'Unpaid',
        ])->assertUnprocessable()->assertJsonValidationErrors(['nomor_invoice']);
    }

    public function test_transaksi_keuangan_validation_rejects_invalid_jenis_and_missing_fields(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/transaksi-keuangan', [
            'tanggal' => '2026-11-16',
            'jenis' => 'Lainnya',
            'kategori' => 'DP',
            'jumlah' => 1000,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['jenis']);

        $this->postJson('/api/v1/transaksi-keuangan', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['tanggal', 'jenis', 'kategori', 'jumlah']);
    }

    public function test_transaksi_keuangan_validation_rejects_invalid_invoice_id(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/transaksi-keuangan', [
            'invoice_id' => 999999,
            'tanggal' => '2026-11-16',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 1000,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['invoice_id']);
    }

    public function test_deleting_transaksi_keuangan_resyncs_invoice_payment(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $order = $this->makeOrder();
        $invoice = Invoice::query()->create([
            'nomor_invoice' => 'INV-DEL-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-15',
            'subtotal' => 1000000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1000000,
            'dibayar' => 0,
            'status' => 'Unpaid',
        ]);

        $create = $this->postJson('/api/v1/transaksi-keuangan', [
            'invoice_id' => $invoice->id,
            'tanggal' => '2026-11-16',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 300000,
        ])->assertCreated();
        $txId = $create->json('data.id');
        $this->assertSame('DP', $invoice->refresh()->status);

        $this->deleteJson('/api/v1/transaksi-keuangan/'.$txId)->assertNoContent();

        $invoice->refresh();
        $this->assertSame('0.00', (string) $invoice->dibayar);
        $this->assertSame('Unpaid', $invoice->status);
    }

    public function test_updating_transaksi_keuangan_resyncs_previous_and_new_invoice(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $order = $this->makeOrder();
        $invoiceA = Invoice::query()->create([
            'nomor_invoice' => 'INV-MOVE-A',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-15',
            'subtotal' => 1000000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 1000000,
            'dibayar' => 0,
            'status' => 'Unpaid',
        ]);
        $invoiceB = Invoice::query()->create([
            'nomor_invoice' => 'INV-MOVE-B',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-15',
            'subtotal' => 500000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 500000,
            'dibayar' => 0,
            'status' => 'Unpaid',
        ]);

        $create = $this->postJson('/api/v1/transaksi-keuangan', [
            'invoice_id' => $invoiceA->id,
            'tanggal' => '2026-11-16',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 400000,
        ])->assertCreated();
        $txId = $create->json('data.id');
        $this->assertSame('400000.00', (string) $invoiceA->refresh()->dibayar);

        $this->putJson('/api/v1/transaksi-keuangan/'.$txId, [
            'invoice_id' => $invoiceB->id,
            'tanggal' => '2026-11-17',
            'jenis' => 'Pemasukan',
            'kategori' => 'Pelunasan',
            'jumlah' => 500000,
        ])->assertOk();

        $invoiceA->refresh();
        $invoiceB->refresh();
        $this->assertSame('0.00', (string) $invoiceA->dibayar);
        $this->assertSame('Unpaid', $invoiceA->status);
        $this->assertSame('500000.00', (string) $invoiceB->dibayar);
        $this->assertSame('Paid', $invoiceB->status);
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
