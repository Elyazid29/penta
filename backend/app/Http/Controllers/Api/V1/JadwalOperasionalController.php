<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJadwalOperasionalRequest;
use App\Http\Requests\UpdateJadwalOperasionalRequest;
use App\Models\JadwalOperasional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JadwalOperasionalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JadwalOperasional::with(['order', 'karyawan', 'armada', 'alats'])->latest('tanggal_mulai');

        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('karyawan_id'), fn ($q) => $q->where('karyawan_id', $request->integer('karyawan_id')));
        $query->when($request->filled('armada_id'), fn ($q) => $q->where('armada_id', $request->integer('armada_id')));
        $query->when($request->filled('form_order_id'), fn ($q) => $q->where('form_order_id', $request->integer('form_order_id')));

        $from = $request->input('from');
        $to = $request->input('to');
        if ($from && $to) {
            $query->where('tanggal_mulai', '<=', $to)->where('tanggal_selesai', '>=', $from);
        } elseif ($from) {
            $query->where('tanggal_selesai', '>=', $from);
        } elseif ($to) {
            $query->where('tanggal_mulai', '<=', $to);
        }

        return response()->json(['data' => $query->paginate()]);
    }

    public function store(StoreJadwalOperasionalRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($conflict = $this->findConflict($data)) {
            return $conflict;
        }

        $jadwal = JadwalOperasional::create($this->withoutAlats($data));
        $this->syncAlats($jadwal, $data['alats'] ?? []);

        return response()->json(['data' => $jadwal->load(['order', 'karyawan', 'armada', 'alats'])], 201);
    }

    public function update(UpdateJadwalOperasionalRequest $request, JadwalOperasional $jadwalOperasional): JsonResponse
    {
        $data = $request->validated();

        if ($conflict = $this->findConflict($data, $jadwalOperasional->id)) {
            return $conflict;
        }

        $jadwalOperasional->update($this->withoutAlats($data));
        if (array_key_exists('alats', $data)) {
            $this->syncAlats($jadwalOperasional, $data['alats'] ?? []);
        }

        return response()->json(['data' => $jadwalOperasional->refresh()->load(['order', 'karyawan', 'armada', 'alats'])]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withoutAlats(array $data): array
    {
        unset($data['alats']);

        return $data;
    }

    /**
     * @param  array<int, array{barang_id: int, qty?: int|null}>  $alats
     */
    private function syncAlats(JadwalOperasional $jadwal, array $alats): void
    {
        $sync = [];
        foreach ($alats as $alat) {
            $sync[(int) $alat['barang_id']] = ['qty' => (int) ($alat['qty'] ?? 1)];
        }
        $jadwal->alats()->sync($sync);
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
