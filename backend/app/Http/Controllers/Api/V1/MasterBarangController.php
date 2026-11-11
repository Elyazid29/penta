<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMasterBarangRequest;
use App\Http\Requests\UpdateMasterBarangRequest;
use App\Http\Resources\MasterBarangResource;
use App\Models\MasterBarang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class MasterBarangController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max(request()->integer('per_page', 15), 1), 100);

        return MasterBarangResource::collection(MasterBarang::query()->latest()->paginate($perPage));
    }

    public function store(StoreMasterBarangRequest $r): MasterBarangResource
    {
        $data = $r->validated();
        if ($r->hasFile('foto')) {
            $data['foto_path'] = $r->file('foto')->store('barang', 'public');
        }
        unset($data['foto']);

        return new MasterBarangResource(MasterBarang::create($data));
    }

    public function show(MasterBarang $masterBarang): MasterBarangResource
    {
        return new MasterBarangResource($masterBarang);
    }

    public function update(UpdateMasterBarangRequest $r, MasterBarang $masterBarang): MasterBarangResource
    {
        $data = $r->validated();
        $previousPhotoPath = $masterBarang->foto_path;
        if ($r->hasFile('foto')) {
            $data['foto_path'] = $r->file('foto')->store('barang', 'public');
        }
        unset($data['foto']);
        $masterBarang->update($data);
        if ($previousPhotoPath !== null && isset($data['foto_path'])) {
            Storage::disk('public')->delete($previousPhotoPath);
        }

        return new MasterBarangResource($masterBarang->refresh());
    }

    public function destroy(MasterBarang $masterBarang): JsonResponse
    {
        $photoPath = $masterBarang->foto_path;
        $masterBarang->delete();
        if ($photoPath !== null) {
            Storage::disk('public')->delete($photoPath);
        }

        return response()->json(status: 204);
    }
}
