<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransaksiKeuanganRequest;
use App\Http\Requests\UpdateTransaksiKeuanganRequest;
use App\Models\Invoice;
use App\Models\TransaksiKeuangan;
use Illuminate\Http\JsonResponse;

class TransaksiKeuanganController extends Controller
{
    public function index(): JsonResponse
    {
        $transactions = TransaksiKeuangan::with(['invoice', 'formOrder'])->latest('tanggal')->paginate();
        $summary = ['pemasukan' => TransaksiKeuangan::where('jenis', 'Pemasukan')->sum('jumlah'), 'pengeluaran' => TransaksiKeuangan::where('jenis', 'Pengeluaran')->sum('jumlah')];
        $summary['saldo'] = $summary['pemasukan'] - $summary['pengeluaran'];

        return response()->json(['data' => $transactions, 'summary' => $summary]);
    }

    public function store(StoreTransaksiKeuanganRequest $request): JsonResponse
    {
        $data = $this->resolveFormOrderId($request->validated());
        $transaction = TransaksiKeuangan::create($data);
        $this->syncInvoicePayment($transaction->invoice_id);

        return response()->json(['data' => $transaction->load(['invoice', 'formOrder'])], 201);
    }

    public function update(UpdateTransaksiKeuanganRequest $request, TransaksiKeuangan $transaksiKeuangan): JsonResponse
    {
        $previousInvoiceId = $transaksiKeuangan->invoice_id;
        $data = $this->resolveFormOrderId($request->validated());
        $transaksiKeuangan->update($data);
        $this->syncInvoicePayment($previousInvoiceId);
        $this->syncInvoicePayment($transaksiKeuangan->invoice_id);

        return response()->json(['data' => $transaksiKeuangan->refresh()->load(['invoice', 'formOrder'])]);
    }

    /**
     * Jika invoice dipilih dan form_order_id belum diisi, isi dari invoice terkait.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function resolveFormOrderId(array $data): array
    {
        if (! empty($data['invoice_id']) && empty($data['form_order_id'])) {
            $data['form_order_id'] = Invoice::query()->whereKey($data['invoice_id'])->value('form_order_id');
        }

        return $data;
    }

    public function destroy(TransaksiKeuangan $transaksiKeuangan): JsonResponse
    {
        $invoiceId = $transaksiKeuangan->invoice_id;
        $transaksiKeuangan->delete();
        $this->syncInvoicePayment($invoiceId);

        return response()->json(status: 204);
    }

    /**
     * Sinkronkan total dibayar & status invoice dari transaksi pemasukan terkait.
     */
    private function syncInvoicePayment(?int $invoiceId): void
    {
        if (! $invoiceId) {
            return;
        }

        $invoice = Invoice::query()->find($invoiceId);
        if (! $invoice) {
            return;
        }

        $dibayar = (float) TransaksiKeuangan::query()
            ->where('invoice_id', $invoiceId)
            ->where('jenis', 'Pemasukan')
            ->sum('jumlah');
        $total = (float) $invoice->total;

        $status = 'Unpaid';
        $isPaid = $total > 0 ? $dibayar >= $total : $dibayar > 0;
        if ($isPaid) {
            $status = 'Paid';
        } elseif ($dibayar > 0) {
            $status = 'DP';
        }

        if ($status !== 'Paid' && $invoice->jatuh_tempo && $invoice->jatuh_tempo->isPast()) {
            $status = 'Overdue';
        }

        $invoice->forceFill([
            'dibayar' => $dibayar,
            'status' => $status,
        ])->save();
    }
}
