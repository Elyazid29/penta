<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterKlienResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'nama_klien' => $this->nama_klien, 'nama_perusahaan' => $this->nama_perusahaan, 'alamat' => $this->alamat, 'contacts' => [['nama' => $this->cp1_nama, 'telp' => $this->cp1_telp], ['nama' => $this->cp2_nama, 'telp' => $this->cp2_telp], ['nama' => $this->cp3_nama, 'telp' => $this->cp3_telp]], 'npwp' => $this->npwp, 'kategori' => $this->kategori];
    }
}
