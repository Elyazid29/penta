<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormOrderItem extends Model
{
    use HasFactory;

    protected $fillable = ['barang_id', 'paket_id', 'nama_item', 'qty', 'harga_harian'];

    protected function casts(): array
    {
        return ['harga_harian' => 'decimal:2'];
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(MasterBarang::class, 'barang_id');
    }

    public function paket(): BelongsTo
    {
        return $this->belongsTo(MasterPaket::class, 'paket_id');
    }
}
