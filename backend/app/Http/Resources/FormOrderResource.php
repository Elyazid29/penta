<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FormOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'nomor_order' => $this->nomor_order, 'klien' => $this->whenLoaded('klien', fn () => ['id' => $this->klien->id, 'nama_klien' => $this->klien->nama_klien, 'nama_perusahaan' => $this->klien->nama_perusahaan]), 'nama_event' => $this->nama_event, 'venue' => $this->venue, 'ruangan' => $this->ruangan, 'lantai' => $this->lantai, 'load_in' => $this->load_in, 'load_out' => $this->load_out, 'status' => $this->status, 'catatan' => $this->catatan, 'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => ['id' => $item->id, 'barang_id' => $item->barang_id, 'paket_id' => $item->paket_id, 'nama_item' => $item->nama_item, 'qty' => $item->qty, 'harga_harian' => $item->harga_harian])->all())];
    }
}
