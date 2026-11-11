<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterArmadaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'jenis_kendaraan' => $this->jenis_kendaraan, 'plat_nomor' => $this->plat_nomor, 'status_stnk' => $this->status_stnk, 'driver' => $this->driver, 'status_operasional' => $this->status_operasional];
    }
}
