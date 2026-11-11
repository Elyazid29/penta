<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormPenawaranResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'nomor_penawaran' => $this->nomor_penawaran, 'form_order_id' => $this->form_order_id, 'order' => $this->whenLoaded('order', fn () => ['nomor_order' => $this->order->nomor_order, 'nama_event' => $this->order->nama_event]), 'tanggal_penawaran' => $this->tanggal_penawaran, 'berlaku_sampai' => $this->berlaku_sampai, 'status' => $this->status, 'subtotal' => $this->subtotal, 'diskon' => $this->diskon, 'pajak' => $this->pajak, 'total' => $this->total, 'catatan' => $this->catatan, 'items' => $this->whenLoaded('items')];
    }
}
