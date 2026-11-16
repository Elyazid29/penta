<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSuratJalanRequest;
use App\Http\Requests\UpdateSuratJalanRequest;
use App\Models\FormOrder;
use App\Models\Invoice;
use App\Models\MasterKaryawan;
use App\Models\SuratJalan;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuratJalanController extends Controller
{
    public function index(): JsonResponse
    {
        $suratJalan = SuratJalan::with(['order', 'items.barang', 'teknisi'])
            ->latest()
            ->paginate()
            ->through(fn (SuratJalan $item): SuratJalan => $this->present($item));

        return response()->json(['data' => $suratJalan]);
    }

    public function store(StoreSuratJalanRequest $request): JsonResponse
    {
        $suratJalan = DB::transaction(function () use ($request): SuratJalan {
            $suratJalan = SuratJalan::create($request->safe()->except(['items', 'teknisi_ids']));

            $suratJalan->items()->createMany($this->resolveItems($request, (int) $request->validated('form_order_id')));

            if ($request->has('teknisi_ids')) {
                $suratJalan->teknisi()->sync($request->validated('teknisi_ids') ?? []);
            }

            $this->ensureInvoiceForCompletedSuratJalan($suratJalan);

            return $suratJalan;
        });

        return response()->json(['data' => $this->present($suratJalan->load(['order', 'items.barang', 'teknisi']))], 201);
    }

    public function update(UpdateSuratJalanRequest $request, SuratJalan $suratJalan): JsonResponse
    {
        DB::transaction(function () use ($request, $suratJalan): void {
            $suratJalan->update($request->safe()->except(['items', 'teknisi_ids']));

            $items = $request->validated('items');

            if (is_array($items) && $items !== []) {
                $suratJalan->items()->delete();
                $suratJalan->items()->createMany($items);
            }

            if ($request->has('teknisi_ids')) {
                $suratJalan->teknisi()->sync($request->validated('teknisi_ids') ?? []);
            }

            $this->ensureInvoiceForCompletedSuratJalan($suratJalan->refresh());
        });

        return response()->json(['data' => $this->present($suratJalan->refresh()->load(['order', 'items.barang', 'teknisi']))]);
    }

    public function destroy(SuratJalan $suratJalan): JsonResponse
    {
        $suratJalan->delete();

        return response()->json(status: 204);
    }

    public function teknisiOptions(): JsonResponse
    {
        $teknisi = MasterKaryawan::where('status', 'Aktif')
            ->where('jabatan', 'like', '%teknis%')
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'jabatan', 'user_id'])
            ->map(fn (MasterKaryawan $karyawan): array => [
                'id' => $karyawan->id,
                'name' => $karyawan->nama_lengkap,
                'jabatan' => $karyawan->jabatan,
                'user_id' => $karyawan->user_id,
            ]);

        return response()->json(['data' => $teknisi]);
    }

    /**
     * Ambil item dari request; kalau kosong, salin dari Form Order.
     *
     * @return array<int, array<string, mixed>>
     */
    private function resolveItems(StoreSuratJalanRequest $request, int $formOrderId): array
    {
        $items = $request->validated('items');

        if (is_array($items) && $items !== []) {
            return array_map(fn (array $item): array => [
                'barang_id' => $item['barang_id'] ?? null,
                'nama_item' => $item['nama_item'],
                'qty' => $item['qty'],
                'status_check' => $item['status_check'] ?? 'Belum Check-out',
            ], $items);
        }

        return FormOrder::with('items')
            ->findOrFail($formOrderId)
            ->items
            ->map(fn ($item): array => [
                'barang_id' => $item->barang_id,
                'nama_item' => $item->nama_item,
                'qty' => $item->qty,
                'status_check' => 'Belum Check-out',
            ])
            ->all();
    }

    private function present(SuratJalan $suratJalan): SuratJalan
    {
        $suratJalan->setRelation('teknisi', $suratJalan->teknisi->map(fn (MasterKaryawan $karyawan): array => [
            'id' => $karyawan->id,
            'name' => $karyawan->nama_lengkap,
            'jabatan' => $karyawan->jabatan,
            'user_id' => $karyawan->user_id,
        ]));

        return $suratJalan;
    }

    /**
     * Saat surat jalan selesai, buat invoice draft untuk order terkait bila belum ada.
     */
    private function ensureInvoiceForCompletedSuratJalan(SuratJalan $suratJalan): void
    {
        if ($suratJalan->status !== 'Selesai') {
            return;
        }

        if (Invoice::query()->where('form_order_id', $suratJalan->form_order_id)->exists()) {
            return;
        }

        $order = FormOrder::with('items')->findOrFail($suratJalan->form_order_id);
        $subtotal = (float) $order->items->sum(fn ($item): float => (float) $item->qty * (float) $item->harga_harian);

        $nomor = 'INV-SJ-'.$suratJalan->id.'-'.now()->format('Ymd');
        if (Invoice::query()->where('nomor_invoice', $nomor)->exists()) {
            $nomor .= '-'.Str::upper(Str::random(4));
        }

        Invoice::query()->create([
            'nomor_invoice' => $nomor,
            'form_order_id' => $order->id,
            'tanggal_invoice' => now()->toDateString(),
            'subtotal' => $subtotal,
            'diskon' => 0,
            'pajak' => 0,
            'total' => $subtotal,
            'dibayar' => 0,
            'status' => 'Unpaid',
            'catatan' => 'Dibuat otomatis saat surat jalan selesai (SJ: '.$suratJalan->nomor_surat_jalan.').',
        ]);
    }
}
