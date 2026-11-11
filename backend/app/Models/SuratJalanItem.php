<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SuratJalanItem extends Model
{
    protected $fillable = ['surat_jalan_id', 'barang_id', 'nama_item', 'qty', 'status_check'];

    public function suratJalan(): BelongsTo
    {
        return $this->belongsTo(SuratJalan::class);
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(MasterBarang::class);
    }
}
