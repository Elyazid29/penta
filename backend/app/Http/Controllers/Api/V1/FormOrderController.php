<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormOrderRequest;
use App\Http\Requests\UpdateFormOrderRequest;
use App\Http\Resources\FormOrderResource;
use App\Models\FormOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class FormOrderController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return FormOrderResource::collection(FormOrder::with(['klien', 'items'])->latest('load_in')->paginate());
    }

    public function store(StoreFormOrderRequest $r): FormOrderResource
    {
        $order = DB::transaction(function () use ($r): FormOrder {
            $order = FormOrder::create($r->safe()->except('items'));
            $order->items()->createMany($r->validated('items'));

            return $order;
        });

        return new FormOrderResource($order->load(['klien', 'items']));
    }

    public function show(FormOrder $formOrder): FormOrderResource
    {
        return new FormOrderResource($formOrder->load(['klien', 'items']));
    }

    public function update(UpdateFormOrderRequest $r, FormOrder $formOrder): FormOrderResource
    {
        $order = DB::transaction(function () use ($r, $formOrder): FormOrder {
            $formOrder->update($r->safe()->except('items'));
            $formOrder->items()->delete();
            $formOrder->items()->createMany($r->validated('items'));

            return $formOrder;
        });

        return new FormOrderResource($order->refresh()->load(['klien', 'items']));
    }

    public function destroy(FormOrder $formOrder): JsonResponse
    {
        $formOrder->delete();

        return response()->json(status: 204);
    }
}
