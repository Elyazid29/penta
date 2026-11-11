<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterPerusahaanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'nama_perusahaan' => $this->nama_perusahaan, 'alamat_kantor' => $this->alamat_kantor, 'telepon' => $this->telepon, 'email' => $this->email, 'kop_surat_path' => $this->kop_surat_path, 'logo_path' => $this->logo_path, 'nama_bank' => $this->nama_bank, 'nomor_rekening' => $this->nomor_rekening, 'atas_nama_rekening' => $this->atas_nama_rekening];
    }
}
