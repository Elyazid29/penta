<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormPenawaranRequest;
use App\Http\Requests\UpdateFormPenawaranRequest;
use App\Http\Resources\FormPenawaranResource;
use App\Models\FormPenawaran;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class FormPenawaranController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return FormPenawaranResource::collection(FormPenawaran::with(['order', 'items'])->latest()->paginate());
    }

    public function store(StoreFormPenawaranRequest $r): FormPenawaranResource
    {
        $p = $this->save($r, null);

        return new FormPenawaranResource($p->load(['order', 'items']));
    }

    public function show(FormPenawaran $formPenawaran): FormPenawaranResource
    {
        return new FormPenawaranResource($formPenawaran->load(['order', 'items']));
    }

    public function update(UpdateFormPenawaranRequest $r, FormPenawaran $formPenawaran): FormPenawaranResource
    {
        $p = $this->save($r, $formPenawaran);

        return new FormPenawaranResource($p->refresh()->load(['order', 'items']));
    }

    public function destroy(FormPenawaran $formPenawaran): JsonResponse
    {
        $formPenawaran->delete();

        return response()->json(status: 204);
    }

    private function save(StoreFormPenawaranRequest|UpdateFormPenawaranRequest $r, ?FormPenawaran $p): FormPenawaran
    {
        return DB::transaction(function () use ($r, $p): FormPenawaran {
            $data = $r->safe()->except('items');
            $p ??= new FormPenawaran;
            $p->fill($data);
            $p->save();
            $p->items()->delete();
            $items = $r->validated('items');
            $subtotal = 0;
            foreach ($items as &$item) {
                $item['subtotal'] = $item['qty'] * $item['harga'];
                $subtotal += $item['subtotal'];
            }unset($item);
            $p->items()->createMany($items);
            $p->update(['subtotal' => $subtotal, 'total' => $subtotal - $p->diskon + $p->pajak]);

            return $p;
        });
    }
}
