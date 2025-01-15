<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DinasLuarKota extends Model
{
    use HasFactory;

    protected $table = 'dinas_luar_kota'; // Nama tabel

    protected $fillable = [
        'id_karyawan',
        'tgl_berangkat',
        'tgl_kembali',
        'kota_tujuan',
        'keperluan',
        'biaya_transport',
        'biaya_penginapan',
        'uang_harian',
        'total_biaya',
        'status',  // Tambahkan status ke dalam fillable
    ];

    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}
