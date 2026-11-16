<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJadwalOperasionalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'form_order_id' => $this->input('form_order_id', $this->input('form_orders_id')),
        ]);
    }

    public function rules(): array
    {
        return [
            'form_order_id' => ['required', 'exists:form_orders,id'],
            'karyawan_id' => ['nullable', 'exists:master_karyawans,id'],
            'armada_id' => ['nullable', 'exists:master_armadas,id'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'jobdesk' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:Terjadwal,Berjalan,Selesai,Dibatalkan'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
