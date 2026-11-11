<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterPerusahaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['nama_perusahaan' => ['required', 'string', 'max:255'], 'alamat_kantor' => ['nullable', 'string'], 'telepon' => ['nullable', 'string', 'max:50'], 'email' => ['nullable', 'email', 'max:255'], 'kop_surat_path' => ['nullable', 'string', 'max:255'], 'logo_path' => ['nullable', 'string', 'max:255'], 'nama_bank' => ['nullable', 'string', 'max:100'], 'nomor_rekening' => ['nullable', 'string', 'max:100'], 'atas_nama_rekening' => ['nullable', 'string', 'max:255']];
    }
}
