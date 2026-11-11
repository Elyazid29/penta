<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterPaket extends Model
{
    use HasFactory;

    protected $table = 'master_pakets';

    protected $fillable = ['kode_paket', 'nama_paket', 'kategori', 'sub_kategori', 'harga_paket', 'satuan', 'catatan'];

    protected function casts(): array
    {
        return ['harga_paket' => 'decimal:2'];
    }

    public function items(): HasMany
    {
        return $this->hasMany(MasterPaketItem::class, 'paket_id');
    }
}
