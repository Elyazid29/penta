<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AbsensiController extends Controller
{
    public function index(): JsonResponse
    {
        $absensis = Absensi::with('user')
            ->orderBy('tanggal', 'desc')
            ->paginate();

        return response()->json(['data' => $absensis]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'tanggal' => 'required|date',
            'status' => 'required|in:Hadir,Izin,Cuti,Sakit,Alfa',
            'keterangan' => 'nullable|string',
        ]);

        $absensi = Absensi::create($validated);

        return response()->json(['data' => $absensi], 201);
    }

    public function update(Request $request, Absensi $absensi): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:Hadir,Izin,Cuti,Sakit,Alfa',
            'keterangan' => 'nullable|string',
        ]);

        $absensi->update($validated);

        return response()->json(['data' => $absensi]);
    }

    public function destroy(Absensi $absensi): JsonResponse
    {
        $absensi->delete();

        return response()->json(status: 204);
    }
}
