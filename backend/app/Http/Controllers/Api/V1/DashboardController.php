<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FormOrder;
use App\Models\FormPenawaran;
use App\Models\MasterArmada;
use App\Models\MasterBarang;
use App\Models\MasterKaryawan;
use App\Models\MasterKlien;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'counts' => [
                'barang' => MasterBarang::count(),
                'karyawan_aktif' => MasterKaryawan::where('status', 'Aktif')->count(),
                'klien' => MasterKlien::count(),
                'armada' => MasterArmada::count(),
                'order_aktif' => FormOrder::whereIn('status', ['Draft', 'Confirmed'])->count(),
                'penawaran_aktif' => FormPenawaran::whereIn('status', ['Draft', 'Dikirim'])->count(),
            ],
            'orders_terbaru' => FormOrder::with('klien')->latest()->limit(5)->get(),
            'penawaran_terbaru' => FormPenawaran::with('order')->latest()->limit(5)->get(),
        ]);
    }
}
