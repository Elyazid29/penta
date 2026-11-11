<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryItemChecklist extends Model
{
    protected $fillable = ['delivery_photo_id', 'surat_jalan_item_id', 'nama_barang', 'qty', 'status_fisik', 'keterangan'];

    public function deliveryPhoto(): BelongsTo
    {
        return $this->belongsTo(DeliveryPhoto::class);
    }
}
