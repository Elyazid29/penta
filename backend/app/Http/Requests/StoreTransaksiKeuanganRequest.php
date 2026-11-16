<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransaksiKeuanganRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'invoice_id' => $this->input('invoice_id', $this->input('invoices_id')),
        ]);
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['nullable', 'exists:invoices,id'],
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', 'in:Pemasukan,Pengeluaran'],
            'kategori' => ['required', 'string', 'max:100'],
            'jumlah' => ['required', 'numeric', 'min:0'],
            'metode' => ['nullable', 'string', 'max:50'],
            'referensi' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string'],
        ];
    }
}
