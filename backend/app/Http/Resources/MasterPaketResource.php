<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterPaketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'kode_paket' => $this->kode_paket,
            'nama_paket' => $this->nama_paket,
            'kategori' => $this->kategori,
            'sub_kategori' => $this->sub_kategori,
            'harga_paket' => $this->harga_paket,
            'satuan' => $this->satuan,
            'catatan' => $this->catatan,
            'items' => $this->whenLoaded('items', fn (): array => $this->items->map(fn ($item): array => [
                'barang_id' => $item->barang_id,
                'nama_item' => $item->nama_item ?? $item->barang?->nama_barang,
                'qty' => $item->qty,
                'satuan' => $item->satuan,
                'harga' => $item->harga,
                'urutan' => $item->urutan,
                'barang' => $item->barang,
            ])->all()),
        ];
    }
}
