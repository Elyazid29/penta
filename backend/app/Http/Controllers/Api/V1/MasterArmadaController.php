<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMasterArmadaRequest;
use App\Http\Requests\UpdateMasterArmadaRequest;
use App\Http\Resources\MasterArmadaResource;
use App\Models\MasterArmada;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MasterArmadaController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MasterArmadaResource::collection(MasterArmada::query()->latest()->paginate());
    }

    public function store(StoreMasterArmadaRequest $r): MasterArmadaResource
    {
        return new MasterArmadaResource(MasterArmada::create($r->validated()));
    }

    public function show(MasterArmada $masterArmada): MasterArmadaResource
    {
        return new MasterArmadaResource($masterArmada);
    }

    public function update(UpdateMasterArmadaRequest $r, MasterArmada $masterArmada): MasterArmadaResource
    {
        $masterArmada->update($r->validated());

        return new MasterArmadaResource($masterArmada->refresh());
    }

    public function destroy(MasterArmada $masterArmada): JsonResponse
    {
        $masterArmada->delete();

        return response()->json(status: 204);
    }
}
