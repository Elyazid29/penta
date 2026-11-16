<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateInvoiceRequest extends StoreInvoiceRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['nomor_invoice'] = ['required', 'string', 'max:50', Rule::unique('invoices', 'nomor_invoice')->ignore($this->route('invoice'))];

        return $rules;
    }
}
