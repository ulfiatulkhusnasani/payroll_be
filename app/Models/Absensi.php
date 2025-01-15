<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensi';

    // Pastikan hanya kolom yang ada di tabel database dimasukkan ke dalam $fillable
    protected $fillable = [
        'id_karyawan',      // Relasi dengan tabel karyawans
        'tanggal',          // Tanggal absensi
        'jam_masuk',        // Jam masuk kerja
        'foto_masuk',       // Foto saat absensi masuk
        'latitude_masuk',   // Lokasi (latitude) saat absensi masuk
        'longitude_masuk',  // Lokasi (longitude) saat absensi masuk
        'status',           // Status absensi (Terlambat/Tepat waktu)
        'jam_keluar',       // Jam keluar kerja
        'foto_keluar',      // Foto saat absensi keluar
        'latitude_keluar',  // Lokasi (latitude) saat absensi keluar
        'longitude_keluar', // Lokasi (longitude) saat absensi keluar
    ];

    // Mutator untuk tanggal (format saat diakses)
    public function getTanggalAttribute($value)
    {
        return Carbon::parse($value)->format('d-m-Y');
    }

    // Akses foto masuk dalam bentuk base64
    public function getFotoMasukBase64Attribute()
    {
        if ($this->foto_masuk) {
            return base64_encode(Storage::disk('public')->get($this->foto_masuk));
        }
        return null;
    }

    // Akses foto keluar dalam bentuk base64
    public function getFotoKeluarBase64Attribute()
    {
        if ($this->foto_keluar) {
            return base64_encode(Storage::disk('public')->get($this->foto_keluar));
        }
        return null;
    }

    // Relasi dengan tabel karyawans
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}