<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function alats(): BelongsToMany
    {
        return $this->belongsToMany(MasterBarang::class, 'jadwal_operasional_alats', 'jadwal_operasional_id', 'master_barang_id')
            ->withPivot('qty')
            ->withTimestamps();
    }
}
