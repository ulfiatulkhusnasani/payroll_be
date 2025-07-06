<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Izin extends Model
{
    use HasFactory;

    protected $table = 'izin'; // Nama tabel di database

    protected $fillable = [
        'id_karyawan',
        'tgl_mulai',
        'tgl_selesai',
        'alasan',
        'keterangan',
        'durasi',
        'status',
        'lampiran',
    ];

    // Relasi ke model Karyawan
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}
