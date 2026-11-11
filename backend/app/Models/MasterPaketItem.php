<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterPaketItem extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'master_paket_items';

    protected $fillable = ['barang_id', 'nama_item', 'qty', 'satuan', 'harga', 'urutan'];

    public function barang(): BelongsTo
    {
        return $this->belongsTo(MasterBarang::class, 'barang_id');
    }
}
