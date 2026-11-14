<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SuratJalan extends Model
{
    protected $fillable = ['nomor_surat_jalan', 'form_order_id', 'tanggal_berangkat', 'status', 'catatan'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FormOrder::class, 'form_order_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SuratJalanItem::class);
    }

    public function teknisi(): BelongsToMany
    {
        return $this->belongsToMany(MasterKaryawan::class, 'surat_jalan_teknisi', 'surat_jalan_id', 'karyawan_id')->withTimestamps();
    }

    public function photos(): HasMany
    {
        return $this->hasMany(DeliveryPhoto::class);
    }
}
