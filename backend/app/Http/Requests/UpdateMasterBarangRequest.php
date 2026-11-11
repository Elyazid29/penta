<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateMasterBarangRequest extends StoreMasterBarangRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['kode_barang'] = ['required', 'string', 'max:50', Rule::unique('master_barangs', 'kode_barang')->ignore($this->route('master_barang'))];

        return $rules;
    }
}
