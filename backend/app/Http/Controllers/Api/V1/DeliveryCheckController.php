<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPhoto;
use App\Models\MasterKaryawan;
use App\Models\SuratJalan;
use App\Services\PhotoStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DeliveryCheckController extends Controller
{
    public function scanSuratJalan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kode_surat_jalan' => 'required|string',
        ]);

        $suratJalan = SuratJalan::with(['items.barang', 'teknisi'])
            ->where('nomor_surat_jalan', $validated['kode_surat_jalan'])
            ->first();

        if (! $suratJalan) {
            return response()->json(['message' => 'Surat Jalan tidak ditemukan.'], 404);
        }

        return response()->json(['data' => [
            'id' => $suratJalan->id,
            'nomor_surat_jalan' => $suratJalan->nomor_surat_jalan,
            'tanggal_berangkat' => $suratJalan->tanggal_berangkat,
            'status' => $suratJalan->status,
            'catatan' => $suratJalan->catatan,
            'items' => $suratJalan->items->map(fn ($item): array => [
                'id' => $item->id,
                'barang_id' => $item->barang_id,
                'nama_item' => $item->nama_item,
                'qty' => $item->qty,
                'status_check' => $item->status_check,
            ])->all(),
            'teknisi' => $suratJalan->teknisi->map(fn (MasterKaryawan $karyawan): array => [
                'id' => $karyawan->id,
                'name' => $karyawan->nama_lengkap,
                'user_id' => $karyawan->user_id,
            ])->all(),
            'is_assigned' => $this->isAssigned($suratJalan, (int) $request->user()->id),
        ]]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'surat_jalan_id' => 'required|exists:surat_jalans,id',
            'foto' => 'required|image|max:5120',
            'items' => 'required|array',
            'items.*.surat_jalan_item_id' => 'nullable|integer',
            'items.*.nama_barang' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.status_fisik' => 'required|in:Baik,Lecet,Rusak,Hilang',
            'items.*.keterangan' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        if ($denied = $this->denyUnassignedTeknisi($request, (int) $validated['surat_jalan_id'])) {
            return $denied;
        }

        $foto = app(PhotoStorage::class)->store($request->file('foto'), 'delivery-photos/checkout');

        $deliveryPhoto = DeliveryPhoto::create([
            'surat_jalan_id' => $validated['surat_jalan_id'],
            'user_id' => $request->user()->id,
            'tipe' => 'checkout',
            'foto_path' => $foto['path'],
            'foto_thumb_path' => $foto['thumb_path'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $deliveryPhoto->checklists()->create($item);
        }

        return response()->json(['data' => $deliveryPhoto->load('checklists')], 201);
    }

    public function checkin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'surat_jalan_id' => 'required|exists:surat_jalans,id',
            'foto' => 'required|image|max:5120',
            'items' => 'required|array',
            'items.*.surat_jalan_item_id' => 'nullable|integer',
            'items.*.nama_barang' => 'required|string',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.status_fisik' => 'required|in:Baik,Lecet,Rusak,Hilang',
            'items.*.keterangan' => 'nullable|string',
            'catatan' => 'nullable|string',
        ]);

        if ($denied = $this->denyUnassignedTeknisi($request, (int) $validated['surat_jalan_id'])) {
            return $denied;
        }

        $foto = app(PhotoStorage::class)->store($request->file('foto'), 'delivery-photos/checkin');

        $deliveryPhoto = DeliveryPhoto::create([
            'surat_jalan_id' => $validated['surat_jalan_id'],
            'user_id' => $request->user()->id,
            'tipe' => 'checkin',
            'foto_path' => $foto['path'],
            'foto_thumb_path' => $foto['thumb_path'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        foreach ($validated['items'] as $item) {
            $deliveryPhoto->checklists()->create($item);
        }

        return response()->json(['data' => $deliveryPhoto->load('checklists')], 201);
    }

    /**
     * Riwayat foto checkout/checkin untuk satu surat jalan.
     */
    public function photos(SuratJalan $suratJalan): JsonResponse
    {
        $photos = $suratJalan->photos()
            ->with(['checklists', 'user:id,name'])
            ->latest()
            ->get();

        return response()->json(['data' => $photos->map(fn (DeliveryPhoto $photo): array => [
            'id' => $photo->id,
            'tipe' => $photo->tipe,
            'catatan' => $photo->catatan,
            'user' => $photo->user?->name,
            'foto_url' => $photo->foto_path ? Storage::disk('public')->url($photo->foto_path) : null,
            'foto_thumb_url' => $photo->foto_thumb_path ? Storage::disk('public')->url($photo->foto_thumb_path) : null,
            'created_at' => $photo->created_at?->toIso8601String(),
            'checklists' => $photo->checklists->map(fn ($item): array => [
                'id' => $item->id,
                'nama_barang' => $item->nama_barang,
                'qty' => $item->qty,
                'status_fisik' => $item->status_fisik,
                'keterangan' => $item->keterangan,
            ])->all(),
        ])->all()]);
    }

    /**
     * Tolak teknisi yang tidak ditugaskan, hanya bila surat jalan punya teknisi
     * yang sudah punya akun login. Admin/Owner selalu boleh.
     */
    private function denyUnassignedTeknisi(Request $request, int $suratJalanId): ?JsonResponse
    {
        if (in_array($request->user()->role?->name, ['admin', 'owner'], true)) {
            return null;
        }

        $suratJalan = SuratJalan::with('teknisi')->findOrFail($suratJalanId);

        if ($this->isAssigned($suratJalan, (int) $request->user()->id)) {
            return null;
        }

        return response()->json(['message' => 'Anda tidak ditugaskan pada Surat Jalan ini.'], 403);
    }

    private function isAssigned(SuratJalan $suratJalan, int $userId): bool
    {
        $teknisi = $suratJalan->relationLoaded('teknisi') ? $suratJalan->teknisi : $suratJalan->teknisi()->get();

        if ($teknisi->isEmpty()) {
            return true;
        }

        $userIds = $teknisi->pluck('user_id')->filter()->map(fn ($id): int => (int) $id);

        if ($userIds->isEmpty()) {
            return true;
        }

        return $userIds->contains($userId);
    }
}
