<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\FormOrder;
use App\Models\FormPenawaran;
use App\Models\Invoice;
use App\Models\JadwalOperasional;
use App\Models\MasterArmada;
use App\Models\MasterBarang;
use App\Models\MasterKaryawan;
use App\Models\MasterKlien;
use App\Models\MasterPaket;
use App\Models\MasterPerusahaan;
use App\Models\Role;
use App\Models\SuratJalan;
use App\Models\TransaksiKeuangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_is_recorded_on_model_changes(): void
    {
        $admin = $this->actingAsRole('admin');

        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Audit']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => MasterKlien::class,
            'auditable_id' => $klien->id,
        ]);
        $created = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klien->id)->where('action', 'created')->first();
        $this->assertNotNull($created);
        $this->assertSame('PT Audit', $created->new_values['nama_klien'] ?? null);

        $klien->update(['nama_klien' => 'PT Audit Baru']);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'updated',
            'auditable_type' => MasterKlien::class,
            'auditable_id' => $klien->id,
        ]);
        $updated = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klien->id)->where('action', 'updated')->latest('id')->first();
        $this->assertNotNull($updated);
        $this->assertSame('PT Audit', $updated->old_values['nama_klien'] ?? null);
        $this->assertSame('PT Audit Baru', $updated->new_values['nama_klien'] ?? null);

        $klienId = $klien->id;
        $klien->delete();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'deleted',
            'auditable_type' => MasterKlien::class,
            'auditable_id' => $klienId,
        ]);
        $deleted = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klienId)->where('action', 'deleted')->latest('id')->first();
        $this->assertNotNull($deleted);
        $this->assertSame('PT Audit Baru', $deleted->old_values['nama_klien'] ?? null);
    }

    public function test_audit_log_endpoint_is_restricted_to_admin_and_owner(): void
    {
        $this->getJson('/api/v1/audit-logs')->assertUnauthorized();

        $this->actingAsRole('teknisi');
        $this->getJson('/api/v1/audit-logs')->assertForbidden();

        $this->actingAsRole('admin');
        $this->getJson('/api/v1/audit-logs')->assertOk()->assertJsonStructure(['data']);

        $this->actingAsRole('owner');
        $this->getJson('/api/v1/audit-logs')->assertOk()->assertJsonStructure(['data']);
    }

    public function test_audit_log_can_be_filtered(): void
    {
        $this->actingAsRole('admin');
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Filter']);
        $klien->update(['nama_klien' => 'PT Filter Updated']);

        $response = $this->getJson('/api/v1/audit-logs?action=updated&auditable_type='.urlencode(MasterKlien::class))
            ->assertOk()
            ->assertJsonPath('data.0.action', 'updated')
            ->assertJsonPath('data.0.auditable_type', MasterKlien::class);

        $this->assertNotNull($response->json('data.0.user'));

        $this->getJson('/api/v1/audit-logs?action=created')
            ->assertOk()
            ->assertJsonPath('data.0.action', 'created');

        $this->getJson('/api/v1/audit-logs?auditable_type='.urlencode(MasterKlien::class))
            ->assertOk()
            ->assertJsonPath('data.0.auditable_type', MasterKlien::class);
    }

    public function test_user_model_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $role = Role::query()->where('name', 'teknisi')->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id, 'name' => 'Teknisi Audit']);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
        ]);
    }

    public function test_master_barang_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $barang = MasterBarang::query()->create([
            'kode_barang' => 'BRG-AUD-001',
            'nama_barang' => 'Barang Audit',
            'kategori' => 'Kamera',
            'satuan' => 'unit',
            'harga_sewa' => 10000,
            'stok_total' => 1,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => MasterBarang::class,
            'auditable_id' => $barang->id,
        ]);
    }

    public function test_invoice_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Invoice Audit']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-AUD-INV-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
        $invoice = Invoice::query()->create([
            'nomor_invoice' => 'INV-AUD-001',
            'form_order_id' => $order->id,
            'tanggal_invoice' => '2026-11-10',
            'subtotal' => 100000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 100000,
            'dibayar' => 0,
            'status' => 'Unpaid',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => Invoice::class,
            'auditable_id' => $invoice->id,
        ]);
    }

    public function test_transaksi_keuangan_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $trx = TransaksiKeuangan::query()->create([
            'tanggal' => '2026-11-10',
            'jenis' => 'Pemasukan',
            'kategori' => 'DP',
            'jumlah' => 100000,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => TransaksiKeuangan::class,
            'auditable_id' => $trx->id,
        ]);
    }

    public function test_form_order_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Order Audit']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-AUD-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event Audit',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => FormOrder::class,
            'auditable_id' => $order->id,
        ]);
    }

    public function test_surat_jalan_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT SJ Audit']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-AUD-SJ-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
        $sj = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-AUD-001',
            'form_order_id' => $order->id,
            'tanggal_berangkat' => now(),
            'status' => 'Draft',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => SuratJalan::class,
            'auditable_id' => $sj->id,
        ]);
    }

    public function test_jadwal_operasional_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Jadwal Audit']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-AUD-JAD-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
        $jadwal = JadwalOperasional::query()->create([
            'form_order_id' => $order->id,
            'tanggal_mulai' => '2026-11-20 08:00:00',
            'tanggal_selesai' => '2026-11-20 17:00:00',
            'status' => 'Terjadwal',
            'jobdesk' => 'Setup',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => JadwalOperasional::class,
            'auditable_id' => $jadwal->id,
        ]);
    }

    public function test_master_perusahaan_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $perusahaan = MasterPerusahaan::query()->create([
            'nama_perusahaan' => 'PT Audit Perusahaan',
            'alamat' => 'Jakarta',
            'telepon' => '021000000',
            'email' => 'audit@perusahaan.test',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => MasterPerusahaan::class,
            'auditable_id' => $perusahaan->id,
        ]);
    }

    public function test_master_armada_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $armada = MasterArmada::query()->create([
            'jenis_kendaraan' => 'Van',
            'plat_nomor' => 'B 1234 AUD',
            'status_stnk' => 'Aktif',
            'driver' => 'Driver',
            'status_operasional' => 'Siap',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => MasterArmada::class,
            'auditable_id' => $armada->id,
        ]);
    }

    public function test_master_karyawan_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $karyawan = MasterKaryawan::query()->create([
            'nama_lengkap' => 'Karyawan Audit',
            'jabatan' => 'Teknisi',
            'status' => 'Aktif',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => MasterKaryawan::class,
            'auditable_id' => $karyawan->id,
        ]);
    }

    public function test_master_paket_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $paket = MasterPaket::query()->create([
            'kode_paket' => 'PKT-AUD-001',
            'nama_paket' => 'Paket Audit',
            'kategori' => 'Paket',
            'harga_paket' => 500000,
            'satuan' => 'paket',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => MasterPaket::class,
            'auditable_id' => $paket->id,
        ]);
    }

    public function test_form_penawaran_changes_are_audited(): void
    {
        $admin = $this->actingAsRole('admin');
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Penawaran Audit']);
        $order = FormOrder::query()->create([
            'nomor_order' => 'ORD-AUD-PNW-001',
            'klien_id' => $klien->id,
            'nama_event' => 'Event',
            'venue' => 'Jakarta',
            'load_in' => now(),
            'load_out' => now()->addDay(),
            'status' => 'Draft',
        ]);
        $penawaran = FormPenawaran::query()->create([
            'nomor_penawaran' => 'PNW-AUD-001',
            'form_order_id' => $order->id,
            'tanggal_penawaran' => '2026-11-10',
            'status' => 'Draft',
            'subtotal' => 100000,
            'diskon' => 0,
            'pajak' => 0,
            'total' => 100000,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'created',
            'auditable_type' => FormPenawaran::class,
            'auditable_id' => $penawaran->id,
        ]);
    }

    public function test_audit_log_endpoint_returns_paginated_structure(): void
    {
        $this->actingAsRole('admin');
        MasterKlien::query()->create(['nama_klien' => 'PT Paging']);

        $this->getJson('/api/v1/audit-logs?per_page=10')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'current_page', 'per_page', 'total']);
    }

    public function test_audit_log_endpoint_returns_latest_first(): void
    {
        $this->actingAsRole('admin');
        $first = MasterKlien::query()->create(['nama_klien' => 'PT Pertama']);
        $second = MasterKlien::query()->create(['nama_klien' => 'PT Kedua']);

        $response = $this->getJson('/api/v1/audit-logs?per_page=10')->assertOk();
        $ids = collect($response->json('data'))->pluck('auditable_id')->all();

        $this->assertContains($second->id, $ids);
        $this->assertContains($first->id, $ids);
        $this->assertLessThan(array_search($first->id, $ids, true), array_search($second->id, $ids, true));
    }

    public function test_audit_log_filter_unknown_value_returns_empty_data(): void
    {
        $this->actingAsRole('admin');
        MasterKlien::query()->create(['nama_klien' => 'PT Ada']);

        $this->getJson('/api/v1/audit-logs?action=tidak-ada')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_audit_log_endpoint_respects_per_page(): void
    {
        $this->actingAsRole('admin');
        MasterKlien::query()->create(['nama_klien' => 'PT A']);
        MasterKlien::query()->create(['nama_klien' => 'PT B']);

        $this->getJson('/api/v1/audit-logs?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('per_page', 1);
    }

    public function test_audit_log_skips_update_when_no_changes(): void
    {
        $this->actingAsRole('admin');
        $klien = MasterKlien::query()->create(['nama_klien' => 'PT Sama']);
        $before = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klien->id)->where('action', 'updated')->count();

        $klien->update(['nama_klien' => 'PT Sama']);

        $after = AuditLog::query()->where('auditable_type', MasterKlien::class)->where('auditable_id', $klien->id)->where('action', 'updated')->count();
        $this->assertSame($before, $after);
    }

    private function actingAsRole(string $roleName): User
    {
        $role = Role::query()->where('name', $roleName)->firstOrFail();
        $user = User::factory()->create(['role_id' => $role->id]);
        Sanctum::actingAs($user);

        return $user;
    }
}
