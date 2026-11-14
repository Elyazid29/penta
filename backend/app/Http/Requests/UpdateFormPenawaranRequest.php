<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormPenawaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['nomor_penawaran' => ['required', 'string', 'max:50', Rule::unique('form_penawarans', 'nomor_penawaran')->ignore($this->route('form_penawaran'))], 'form_orders_id' => ['required', 'exists:form_orders,id'], 'tanggal_penawaran' => ['required', 'date'], 'berlaku_sampai' => ['nullable', 'date', 'after_or_equal:tanggal_penawaran'], 'status' => ['required', 'in:Draft,Dikirim,Disetujui,Ditolak'], 'diskon' => ['required', 'numeric', 'min:0'], 'pajak' => ['required', 'numeric', 'min:0'], 'catatan' => ['nullable', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.nama_item' => ['required', 'string', 'max:255'], 'items.*.qty' => ['required', 'integer', 'min:1'], 'items.*.harga' => ['required', 'numeric', 'min:0']];
    }
}
