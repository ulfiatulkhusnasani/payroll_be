<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    // Table name
    protected $table = 'tasks';

    // Primary key
    protected $primaryKey = 'id_tugas';

    // Auto increment settings
    public $incrementing = true;
    protected $keyType = 'int';

    // Fillable columns
    protected $fillable = [
        'id_karyawan',
        'judul_proyek',
        'kegiatan',
        'status',
        'tgl_mulai',
        'batas_penyelesaian',
        'tgl_selesai',
        'point',             // Tambahkan kolom 'point' 
        'status_approval',   // Tambahkan kolom 'status_approval'
    ];

    // Fixed relationship - using 'id' as the referenced key in the karyawans table
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan', 'id');
    }
}