<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateinvoicesRequest extends StoreinvoicesRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['nomor_invoices'] = ['required', 'string', 'max:50', Rule::unique('invoices', 'nomor_invoices')->ignore($this->route('invoice'))];

        return $rules;
    }
}
