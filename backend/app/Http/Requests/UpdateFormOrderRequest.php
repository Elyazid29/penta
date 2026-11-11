<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['nomor_order' => ['required', 'string', 'max:50', Rule::unique('form_orders', 'nomor_order')->ignore($this->route('form_orders'))], 'klien_id' => ['required', 'exists:master_kliens,id'], 'nama_event' => ['required', 'string', 'max:255'], 'venue' => ['required', 'string', 'max:255'], 'ruangan' => ['nullable', 'string', 'max:100'], 'lantai' => ['nullable', 'string', 'max:50'], 'load_in' => ['required', 'date'], 'load_out' => ['required', 'date', 'after_or_equal:load_in'], 'status' => ['required', 'in:Draft,Confirmed,Cancelled'], 'catatan' => ['nullable', 'string'], 'items' => ['required', 'array', 'min:1'], 'items.*.barang_id' => ['nullable', 'exists:master_barangs,id'], 'items.*.paket_id' => ['nullable', 'exists:master_pakets,id'], 'items.*.nama_item' => ['required', 'string', 'max:255'], 'items.*.qty' => ['required', 'integer', 'min:1'], 'items.*.harga_harian' => ['required', 'numeric', 'min:0']];
    }
}
