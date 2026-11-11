<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMasterKlienRequest;
use App\Http\Requests\UpdateMasterKlienRequest;
use App\Http\Resources\MasterKlienResource;
use App\Models\MasterKlien;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterKlienController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max(request()->integer('per_page', 15), 1), 100);

        return MasterKlienResource::collection(MasterKlien::query()->latest()->paginate($perPage));
    }

    public function store(StoreMasterKlienRequest $r): MasterKlienResource
    {
        return new MasterKlienResource(MasterKlien::create($r->validated()));
    }

    public function show(MasterKlien $masterKlien): MasterKlienResource
    {
        return new MasterKlienResource($masterKlien);
    }

    public function update(UpdateMasterKlienRequest $r, MasterKlien $masterKlien): MasterKlienResource
    {
        $masterKlien->update($r->validated());

        return new MasterKlienResource($masterKlien->refresh());
    }

    public function destroy(MasterKlien $masterKlien): JsonResponse
    {
        $masterKlien->delete();

        return response()->json(status: 204);
    }
}
