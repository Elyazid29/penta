<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Invoice::with('order')->latest()->paginate()]);
    }

    public function store(StoreInvoiceRequest $request): JsonResponse
    {
        return response()->json(['data' => Invoice::create($request->validated())->load('order')], 201);
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): JsonResponse
    {
        $invoice->update($request->validated());

        return response()->json(['data' => $invoice->refresh()->load('order')]);
    }

    public function destroy(Invoice $invoice): JsonResponse
    {
        $invoice->delete();

        return response()->json(status: 204);
    }
}
