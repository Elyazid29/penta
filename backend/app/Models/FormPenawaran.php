<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormPenawaran extends Model
{
    use HasFactory;

    protected $table = 'form_penawaran';

    protected $fillable = ['nomor_penawaran', 'form_order_id', 'tanggal_penawaran', 'berlaku_sampai', 'status', 'subtotal', 'diskon', 'pajak', 'total', 'catatan'];

    protected function casts(): array
    {
        return ['tanggal_penawaran' => 'date', 'berlaku_sampai' => 'date', 'subtotal' => 'decimal:2', 'diskon' => 'decimal:2', 'pajak' => 'decimal:2', 'total' => 'decimal:2'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(FormOrder::class, 'form_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FormPenawaranItem::class);
    }
}
