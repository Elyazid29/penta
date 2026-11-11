<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalOperasional extends Model
{
    protected $fillable = ['form_order_id', 'karyawan_id', 'armada_id', 'tanggal_mulai', 'tanggal_selesai', 'jobdesk', 'status', 'catatan'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FormOrder::class, 'form_order_id');
    }

    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(MasterKaryawan::class);
    }

    public function armada(): BelongsTo
    {
        return $this->belongsTo(MasterArmada::class);
    }
}
