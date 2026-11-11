<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAdditionalItem;
use App\Services\PhotoStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdditionalItemController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'surat_jalan_id' => 'required|exists:surat_jalans,id',
            'nama_barang' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
            'foto' => 'nullable|image|max:5120',
            'alasan' => 'required|string',
        ]);

        $fotoPath = null;
        $fotoThumbPath = null;
        if ($request->hasFile('foto')) {
            $foto = app(PhotoStorage::class)->store($request->file('foto'), 'delivery-additional-items');
            $fotoPath = $foto['path'];
            $fotoThumbPath = $foto['thumb_path'];
        }

        $item = DeliveryAdditionalItem::create([
            'surat_jalan_id' => $validated['surat_jalan_id'],
            'user_id' => $request->user()->id,
            'nama_barang' => $validated['nama_barang'],
            'qty' => $validated['qty'],
            'foto_path' => $fotoPath,
            'foto_thumb_path' => $fotoThumbPath,
            'alasan' => $validated['alasan'],
        ]);

        return response()->json(['data' => $item], 201);
    }

    public function indexBySuratJalan(Request $request, $suratJalanId): JsonResponse
    {
        $items = DeliveryAdditionalItem::where('surat_jalan_id', $suratJalanId)

            ->latest()
            ->paginate();

        return response()->json(['data' => $items]);
    }
}
