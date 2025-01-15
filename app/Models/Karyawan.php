<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Karyawan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_karyawan',
        'nip',
        'nik',
        'email',
        'no_handphone',
        'alamat',
        'password',
        'jabatan_id',
        'device_code',
        'avatar',
    ];

    // Hidden attributes (like password)
    protected $hidden = ['password'];

    // Appended attributes
    protected $appends = ['jabatan_nama'];


    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }
 
    public function izins()
    {
        return $this->hasMany(Izin::class, 'id_karyawan');
    }

    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'id_karyawan');
    }

 
    public function getJabatanNamaAttribute()
    {
        return $this->jabatan ? $this->jabatan->jabatan : null;
    }
    // Relationship with Task
    public function tasks()
    {
        return $this->hasMany(Task::class, 'id_karyawan', 'id');
    }
}