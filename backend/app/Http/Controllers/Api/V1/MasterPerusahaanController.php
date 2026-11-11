<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMasterPerusahaanRequest;
use App\Http\Requests\UpdateMasterPerusahaanRequest;
use App\Http\Resources\MasterPerusahaanResource;
use App\Models\MasterPerusahaan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterPerusahaanController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MasterPerusahaanResource::collection(MasterPerusahaan::query()->latest()->paginate());
    }

    public function store(StoreMasterPerusahaanRequest $r): MasterPerusahaanResource
    {
        return new MasterPerusahaanResource(MasterPerusahaan::create($r->validated()));
    }

    public function show(MasterPerusahaan $masterPerusahaan): MasterPerusahaanResource
    {
        return new MasterPerusahaanResource($masterPerusahaan);
    }

    public function update(UpdateMasterPerusahaanRequest $r, MasterPerusahaan $masterPerusahaan): MasterPerusahaanResource
    {
        $masterPerusahaan->update($r->validated());

        return new MasterPerusahaanResource($masterPerusahaan->refresh());
    }

    public function destroy(MasterPerusahaan $masterPerusahaan): JsonResponse
    {
        $masterPerusahaan->delete();

        return response()->json(status: 204);
    }
}
