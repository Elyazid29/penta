<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormOrder extends Model
{
    use HasFactory;

    protected $table = 'form_orders';

    protected $fillable = ['nomor_order', 'klien_id', 'nama_event', 'venue', 'ruangan', 'lantai', 'load_in', 'load_out', 'status', 'catatan'];

    protected function casts(): array
    {
        return ['load_in' => 'datetime', 'load_out' => 'datetime'];
    }

    public function klien(): BelongsTo
    {
        return $this->belongsTo(MasterKlien::class, 'klien_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FormOrderItem::class);
    }
}
