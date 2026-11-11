<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterKaryawan extends Model
{
    use HasFactory;

    protected $table = 'master_karyawans';

    protected $fillable = ['user_id', 'nama_lengkap', 'nik', 'no_ktp', 'jabatan', 'no_telp', 'no_rekening', 'ktp_path', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
