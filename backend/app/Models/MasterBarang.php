<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterBarang extends Model
{
    use HasFactory;

    protected $table = 'master_barangs';

    protected $fillable = ['kode_barang', 'nama_barang', 'kategori', 'satuan', 'deskripsi', 'foto_path', 'jumlah_total', 'jumlah_rusak', 'di_sewa', 'harga_rental'];

    protected function casts(): array
    {
        return ['harga_rental' => 'decimal:2'];
    }
}
