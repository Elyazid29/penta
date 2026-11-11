<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSuratJalanRequest extends StoreSuratJalanRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['nomor_surat_jalan'] = ['required', 'string', 'max:50', Rule::unique('surat_jalans', 'nomor_surat_jalan')->ignore($this->route('surat_jalan'))];

        return $rules;
    }
}
