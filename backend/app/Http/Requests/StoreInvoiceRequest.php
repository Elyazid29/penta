<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreinvoicesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['nomor_invoices' => ['required', 'string', 'max:50', 'unique:invoices,nomor_invoices'], 'form_orders_id' => ['required', 'exists:form_orders,id'], 'tanggal_invoices' => ['required', 'date'], 'jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal_invoices'], 'subtotal' => ['required', 'numeric', 'min:0'], 'diskon' => ['required', 'numeric', 'min:0'], 'pajak' => ['required', 'numeric', 'min:0'], 'total' => ['required', 'numeric', 'min:0'], 'dibayar' => ['required', 'numeric', 'min:0', 'lte:total'], 'status' => ['required', 'in:Unpaid,DP,Paid,Overdue'], 'catatan' => ['nullable', 'string']];
    }
}
