<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nomor_invoice' => $this->input('nomor_invoice', $this->input('nomor_invoices')),
            'form_order_id' => $this->input('form_order_id', $this->input('form_orders_id')),
            'tanggal_invoice' => $this->input('tanggal_invoice', $this->input('tanggal_invoices')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nomor_invoice' => ['required', 'string', 'max:50', 'unique:invoices,nomor_invoice'],
            'form_order_id' => ['required', 'exists:form_orders,id'],
            'tanggal_invoice' => ['required', 'date'],
            'jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal_invoice'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'diskon' => ['required', 'numeric', 'min:0'],
            'pajak' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'dibayar' => ['required', 'numeric', 'min:0', 'lte:total'],
            'status' => ['required', 'in:Unpaid,DP,Paid,Overdue'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
