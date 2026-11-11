<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterKlienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['nama_klien' => ['required', 'string', 'max:255'], 'nama_perusahaan' => ['nullable', 'string', 'max:255'], 'alamat' => ['nullable', 'string'], 'cp1_nama' => ['nullable', 'string', 'max:100'], 'cp1_telp' => ['nullable', 'string', 'max:50'], 'cp2_nama' => ['nullable', 'string', 'max:100'], 'cp2_telp' => ['nullable', 'string', 'max:50'], 'cp3_nama' => ['nullable', 'string', 'max:100'], 'cp3_telp' => ['nullable', 'string', 'max:50'], 'npwp' => ['nullable', 'string', 'max:100'], 'kategori' => ['nullable', 'string', 'max:100']];
    }
}
