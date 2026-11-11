<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterKlien extends Model
{
    use HasFactory;

    protected $table = 'master_kliens';

    protected $fillable = ['nama_klien', 'nama_perusahaan', 'alamat', 'cp1_nama', 'cp1_telp', 'cp2_nama', 'cp2_telp', 'cp3_nama', 'cp3_telp', 'npwp', 'kategori'];
}
