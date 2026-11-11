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
        return response()->json(['data' => ['orders' => FormOrder::count(), 'invoices' => Invoice::count(), 'barang' => MasterBarang::count(), 'pemasukan' => TransaksiKeuangan::where('jenis', 'Pemasukan')->sum('jumlah'), 'pengeluaran' => TransaksiKeuangan::where('jenis', 'Pengeluaran')->sum('jumlah')]]);
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
}
