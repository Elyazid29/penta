<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterArmada extends Model
{
    use HasFactory;

    protected $table = 'master_armadas';

    protected $fillable = ['jenis_kendaraan', 'plat_nomor', 'status_stnk', 'driver', 'status_operasional'];
}
