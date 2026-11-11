<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSuratJalanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nomor_surat_jalan' => ['required', 'string', 'max:50', 'unique:surat_jalans,nomor_surat_jalan'],
            'form_order_id' => ['required', 'exists:form_orders,id'],
            'tanggal_berangkat' => ['required', 'date'],
            'status' => ['required', 'in:Draft,Berangkat,Selesai,Dibatalkan'],
            'catatan' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.barang_id' => ['nullable', 'exists:master_barangs,id'],
            'items.*.nama_item' => ['required', 'string'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.status_check' => ['nullable', 'in:Belum Check-out,Check-out,Check-in'],
            'teknisi_ids' => ['nullable', 'array'],
            'teknisi_ids.*' => ['integer', 'exists:master_karyawans,id'],
        ];
    }
}
