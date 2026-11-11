<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMasterPaketRequest;
use App\Http\Requests\UpdateMasterPaketRequest;
use App\Http\Resources\MasterPaketResource;
use App\Models\MasterPaket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MasterPaketController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $perPage = min(max(request()->integer('per_page', 15), 1), 100);

        return MasterPaketResource::collection(MasterPaket::with('items.barang')->latest()->paginate($perPage));
    }

    public function store(StoreMasterPaketRequest $request): MasterPaketResource
    {
        $paket = DB::transaction(function () use ($request): MasterPaket {
            $paket = MasterPaket::create($request->safe()->except('items'));
            $paket->items()->createMany($request->validated('items'));

            return $paket;
        });

        return new MasterPaketResource($paket->load('items.barang'));
    }

    public function show(MasterPaket $masterPaket): MasterPaketResource
    {
        return new MasterPaketResource($masterPaket->load('items.barang'));
    }

    public function update(UpdateMasterPaketRequest $request, MasterPaket $masterPaket): MasterPaketResource
    {
        $paket = DB::transaction(function () use ($request, $masterPaket): MasterPaket {
            $masterPaket->update($request->safe()->except('items'));
            $masterPaket->items()->delete();
            $masterPaket->items()->createMany($request->validated('items'));

            return $masterPaket;
        });

        return new MasterPaketResource($paket->refresh()->load('items.barang'));
    }

    public function destroy(MasterPaket $masterPaket): JsonResponse
    {
        $masterPaket->delete();

        return response()->json(status: 204);
    }
}
