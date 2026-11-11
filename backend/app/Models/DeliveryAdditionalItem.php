<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryAdditionalItem extends Model
{
    protected $fillable = ['surat_jalan_id', 'user_id', 'nama_barang', 'qty', 'foto_path', 'foto_thumb_path', 'alasan'];

    public function suratJalan(): BelongsTo
    {
        return $this->belongsTo(SuratJalan::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
