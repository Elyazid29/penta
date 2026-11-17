<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($request->query('auditable_type'), fn ($q, $type) => $q->where('auditable_type', $type))
            ->when($request->query('action'), fn ($q, $action) => $q->where('action', $action))
            ->latest()
            ->paginate((int) $request->query('per_page', 50));

        return response()->json($logs);
    }
}
