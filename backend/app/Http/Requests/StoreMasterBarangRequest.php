<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMasterBarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['kode_barang' => ['required', 'string', 'max:50', 'unique:master_barangs,kode_barang'], 'nama_barang' => ['required', 'string', 'max:255'], 'kategori' => ['required', 'string', 'max:100'], 'satuan' => ['nullable', 'string', 'max:30'], 'deskripsi' => ['nullable', 'string'], 'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'], 'jumlah_total' => ['required', 'integer', 'min:0'], 'jumlah_rusak' => ['required', 'integer', 'min:0', 'lte:jumlah_total'], 'di_sewa' => ['required', 'integer', 'min:0', 'lte:jumlah_total'], 'harga_rental' => ['required', 'numeric', 'min:0']];
    }
}
