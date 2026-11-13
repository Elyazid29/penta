<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMasterPaketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'kode_paket' => ['required', 'string', 'max:50', Rule::unique('master_pakets', 'kode_paket')->ignore($this->route('master_paket'))],
            'nama_paket' => ['required', 'string', 'max:255'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'sub_kategori' => ['nullable', 'string', 'max:150'],
            'harga_paket' => ['required', 'numeric', 'min:0'],
            'satuan' => ['nullable', 'string', 'max:30'],
            'catatan' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.nama_item' => ['required', 'string', 'max:255'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.satuan' => ['nullable', 'string', 'max:30'],
            'items.*.harga' => ['nullable', 'numeric', 'min:0'],
            'items.*.urutan' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
