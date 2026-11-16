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
        $data = $request->validated();

        if ($conflict = $this->findConflict($data)) {
            return $conflict;
        }

        return response()->json(['data' => JadwalOperasional::create($data)->load(['order', 'karyawan', 'armada'])], 201);
    }

    public function update(UpdateJadwalOperasionalRequest $request, JadwalOperasional $jadwalOperasional): JsonResponse
    {
        $data = $request->validated();

        if ($conflict = $this->findConflict($data, $jadwalOperasional->id)) {
            return $conflict;
        }

        $jadwalOperasional->update($data);

        return response()->json(['data' => $jadwalOperasional->refresh()->load(['order', 'karyawan', 'armada'])]);
    }

    public function destroy(JadwalOperasional $jadwalOperasional): JsonResponse
    {
        $jadwalOperasional->delete();

        return response()->json(status: 204);
    }

    /**
     * Cek bentrok jadwal crew/armada pada rentang waktu yang sama.
     *
     * @param  array<string, mixed>  $data
     */
    private function findConflict(array $data, ?int $ignoreId = null): ?JsonResponse
    {
        $mulai = (string) $data['tanggal_mulai'];
        $selesai = (string) $data['tanggal_selesai'];

        $base = JadwalOperasional::query()
            ->where('status', '!=', 'Dibatalkan')
            ->where('tanggal_mulai', '<', $selesai)
            ->where('tanggal_selesai', '>', $mulai)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId));

        $karyawanId = $data['karyawan_id'] ?? null;
        if ($karyawanId && (clone $base)->where('karyawan_id', $karyawanId)->exists()) {
            return response()->json([
                'message' => 'Jadwal bentrok: karyawan sudah ditugaskan pada rentang waktu tersebut.',
            ], 422);
        }

        $armadaId = $data['armada_id'] ?? null;
        if ($armadaId && (clone $base)->where('armada_id', $armadaId)->exists()) {
            return response()->json([
                'message' => 'Jadwal bentrok: armada sudah digunakan pada rentang waktu tersebut.',
            ], 422);
        }

        return null;
    }
}
