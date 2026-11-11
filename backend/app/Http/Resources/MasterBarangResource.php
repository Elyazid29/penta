<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterBarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'kode_barang' => $this->kode_barang, 'nama_barang' => $this->nama_barang, 'kategori' => $this->kategori, 'satuan' => $this->satuan, 'deskripsi' => $this->deskripsi, 'foto_path' => $this->foto_path, 'jumlah_total' => $this->jumlah_total, 'jumlah_rusak' => $this->jumlah_rusak, 'di_sewa' => $this->di_sewa, 'harga_rental' => $this->harga_rental];
    }
}
