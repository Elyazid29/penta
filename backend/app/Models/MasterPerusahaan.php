<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPerusahaan extends Model
{
    use HasFactory;

    protected $table = 'master_perusahaans';

    protected $fillable = ['nama_perusahaan', 'alamat_kantor', 'telepon', 'email', 'kop_surat_path', 'logo_path', 'nama_bank', 'nomor_rekening', 'atas_nama_rekening'];
}
