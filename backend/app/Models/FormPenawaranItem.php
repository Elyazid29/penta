<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormPenawaranItem extends Model
{
    use HasFactory;

    protected $fillable = ['nama_item', 'qty', 'harga', 'subtotal'];

    protected function casts(): array
    {
        return ['harga' => 'decimal:2', 'subtotal' => 'decimal:2'];
    }

    public function penawaran(): BelongsTo
    {
        return $this->belongsTo(FormPenawaran::class, 'form_penawaran_id');
    }
}
