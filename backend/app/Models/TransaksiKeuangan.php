<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiKeuangan extends Model
{
    protected $fillable = ['invoice_id', 'form_order_id', 'tanggal', 'jenis', 'kategori', 'jumlah', 'metode', 'referensi', 'keterangan'];

    protected function casts(): array
    {
        return ['jumlah' => 'decimal:2'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function formOrder(): BelongsTo
    {
        return $this->belongsTo(FormOrder::class, 'form_order_id');
    }
}
