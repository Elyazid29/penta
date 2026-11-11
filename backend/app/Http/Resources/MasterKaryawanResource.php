<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MasterKaryawanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'user_id' => $this->user_id, 'email' => $this->whenLoaded('user', fn () => $this->user?->email), 'nama_lengkap' => $this->nama_lengkap, 'nik' => $this->nik, 'no_ktp' => $this->no_ktp, 'jabatan' => $this->jabatan, 'no_telp' => $this->no_telp, 'no_rekening' => $this->no_rekening, 'ktp_path' => $this->ktp_path, 'status' => $this->status];
    }
}
