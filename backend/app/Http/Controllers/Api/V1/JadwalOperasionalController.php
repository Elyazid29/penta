<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJadwalOperasionalRequest;
use App\Http\Requests\UpdateJadwalOperasionalRequest;
use App\Models\JadwalOperasional;
use Illuminate\Http\JsonResponse;

class JadwalOperasionalController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => JadwalOperasional::with(['order', 'karyawan', 'armada'])->latest('tanggal_mulai')->paginate()]);
    }

    public function store(StoreJadwalOperasionalRequest $request): JsonResponse
    {
        return response()->json(['data' => JadwalOperasional::create($request->validated())->load(['order', 'karyawan', 'armada'])], 201);
    }

    public function update(UpdateJadwalOperasionalRequest $request, JadwalOperasional $jadwalOperasional): JsonResponse
    {
        $jadwalOperasional->update($request->validated());

        return response()->json(['data' => $jadwalOperasional->refresh()->load(['order', 'karyawan', 'armada'])]);
    }

    public function destroy(JadwalOperasional $jadwalOperasional): JsonResponse
    {
        $jadwalOperasional->delete();

        return response()->json(status: 204);
    }
}
