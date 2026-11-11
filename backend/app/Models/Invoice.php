<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = ['nomor_invoice', 'form_order_id', 'tanggal_invoice', 'jatuh_tempo', 'subtotal', 'diskon', 'pajak', 'total', 'dibayar', 'status', 'catatan'];

    protected function casts(): array
    {
        return ['tanggal_invoice' => 'date', 'jatuh_tempo' => 'date', 'subtotal' => 'decimal:2', 'diskon' => 'decimal:2', 'pajak' => 'decimal:2', 'total' => 'decimal:2', 'dibayar' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FormOrder::class, 'form_order_id');
    }
}
