<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterArmadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['jenis_kendaraan' => ['required', 'string', 'max:100'], 'plat_nomor' => ['required', 'string', 'max:20', 'unique:master_armadas,plat_nomor'], 'status_stnk' => ['required', 'in:Aktif,Jatuh Tempo,Non-Aktif'], 'driver' => ['nullable', 'string', 'max:255'], 'status_operasional' => ['required', 'in:Standby,Jalan,Servis']];
    }
}
