<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FormOrder;
use App\Models\Invoice;
use App\Models\MasterBarang;
use App\Models\TransaksiKeuangan;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanController extends Controller
{
    public function summary(): JsonResponse
    {
        Invoice::query()
            ->where('status', '!=', 'Paid')
            ->whereNotNull('jatuh_tempo')
            ->whereDate('jatuh_tempo', '<', now()->toDateString())
            ->update(['status' => 'Overdue']);

        $pemasukan = (float) TransaksiKeuangan::query()->where('jenis', 'Pemasukan')->sum('jumlah');
        $pengeluaran = (float) TransaksiKeuangan::query()->where('jenis', 'Pengeluaran')->sum('jumlah');
        $piutang = (float) Invoice::query()
            ->selectRaw('COALESCE(SUM(CASE WHEN total > dibayar THEN total - dibayar ELSE 0 END), 0) as piutang')
            ->value('piutang');

        return response()->json(['data' => [
            'orders' => FormOrder::count(),
            'invoices' => Invoice::count(),
            'barang' => MasterBarang::count(),
            'pemasukan' => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'saldo' => $pemasukan - $pengeluaran,
            'piutang' => $piutang,
            'invoice_overdue' => Invoice::query()->where('status', 'Overdue')->count(),
        ]]);
    }

    public function transactionsCsv(): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tanggal', 'Jenis', 'Kategori', 'Jumlah', 'Metode', 'Referensi', 'Keterangan']);
            TransaksiKeuangan::query()->latest('tanggal')->chunk(500, function ($transactions) use ($handle): void {
                foreach ($transactions as $transaction) {
                    fputcsv($handle, [$transaction->tanggal, $transaction->jenis, $transaction->kategori, $transaction->jumlah, $transaction->metode, $transaction->referensi, $transaction->keterangan]);
                }
            });
            fclose($handle);
        }, 'laporan-keuangan.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function invoicesCsv(): StreamedResponse
    {
        Invoice::query()
            ->where('status', '!=', 'Paid')
            ->whereNotNull('jatuh_tempo')
            ->whereDate('jatuh_tempo', '<', now()->toDateString())
            ->update(['status' => 'Overdue']);

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nomor Invoice', 'Order ID', 'Tanggal', 'Jatuh Tempo', 'Subtotal', 'Diskon', 'Pajak', 'Total', 'Dibayar', 'Sisa', 'Status']);
            Invoice::query()->with('order')->latest('tanggal_invoice')->chunk(500, function ($invoices) use ($handle): void {
                foreach ($invoices as $invoice) {
                    $sisa = max((float) $invoice->total - (float) $invoice->dibayar, 0);
                    fputcsv($handle, [
                        $invoice->nomor_invoice,
                        $invoice->form_order_id,
                        $invoice->tanggal_invoice?->toDateString(),
                        $invoice->jatuh_tempo?->toDateString(),
                        $invoice->subtotal,
                        $invoice->diskon,
                        $invoice->pajak,
                        $invoice->total,
                        $invoice->dibayar,
                        $sisa,
                        $invoice->status,
                    ]);
                }
            });
            fclose($handle);
        }, 'laporan-invoice.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
