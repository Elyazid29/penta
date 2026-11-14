<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMasterArmadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['jenis_kendaraan' => ['required', 'string', 'max:100'], 'plat_nomor' => ['required', 'string', 'max:20', Rule::unique('master_armadas', 'plat_nomor')->ignore($this->route('master_armada'))], 'status_stnk' => ['required', 'in:Aktif,Jatuh Tempo,Non-Aktif'], 'driver' => ['nullable', 'string', 'max:255'], 'status_operasional' => ['required', 'in:Standby,Jalan,Servis']];
    }
}
