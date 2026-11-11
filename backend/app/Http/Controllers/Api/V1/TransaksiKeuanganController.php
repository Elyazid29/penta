<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransaksiKeuanganRequest;
use App\Http\Requests\UpdateTransaksiKeuanganRequest;
use App\Models\TransaksiKeuangan;
use Illuminate\Http\JsonResponse;

class TransaksiKeuanganController extends Controller
{
    public function index(): JsonResponse
    {
        $transactions = TransaksiKeuangan::with('invoice')->latest('tanggal')->paginate();
        $summary = ['pemasukan' => TransaksiKeuangan::where('jenis', 'Pemasukan')->sum('jumlah'), 'pengeluaran' => TransaksiKeuangan::where('jenis', 'Pengeluaran')->sum('jumlah')];
        $summary['saldo'] = $summary['pemasukan'] - $summary['pengeluaran'];

        return response()->json(['data' => $transactions, 'summary' => $summary]);
    }

    public function store(StoreTransaksiKeuanganRequest $request): JsonResponse
    {
        return response()->json(['data' => TransaksiKeuangan::create($request->validated())->load('invoice')], 201);
    }

    public function update(UpdateTransaksiKeuanganRequest $request, TransaksiKeuangan $transaksiKeuangan): JsonResponse
    {
        $transaksiKeuangan->update($request->validated());

        return response()->json(['data' => $transaksiKeuangan->refresh()->load('invoice')]);
    }

    public function destroy(TransaksiKeuangan $transaksiKeuangan): JsonResponse
    {
        $transaksiKeuangan->delete();

        return response()->json(status: 204);
    }
}
