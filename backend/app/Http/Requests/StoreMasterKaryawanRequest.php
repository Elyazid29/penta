<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterKaryawanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['nama_lengkap' => ['required', 'string', 'max:255'], 'nik' => ['nullable', 'string', 'max:50'], 'no_ktp' => ['nullable', 'string', 'max:50'], 'jabatan' => ['required', 'string', 'max:100'], 'no_telp' => ['nullable', 'string', 'max:50'], 'no_rekening' => ['nullable', 'string', 'max:100'], 'ktp_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'], 'status' => ['required', 'in:Aktif,Non-Aktif'], 'email' => ['nullable', 'email', 'max:255', 'unique:users,email'], 'password' => ['nullable', 'string', 'min:8', 'required_with:email']];
    }
}
