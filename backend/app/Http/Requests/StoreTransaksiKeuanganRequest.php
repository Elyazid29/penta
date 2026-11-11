<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransaksiKeuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['invoices_id' => ['nullable', 'exists:invoices,id'], 'tanggal' => ['required', 'date'], 'jenis' => ['required', 'in:Pemasukan,Pengeluaran'], 'kategori' => ['required', 'string', 'max:100'], 'jumlah' => ['required', 'numeric', 'min:0'], 'metode' => ['nullable', 'string', 'max:50'], 'referensi' => ['nullable', 'string', 'max:100'], 'keterangan' => ['nullable', 'string']];
    }
}
