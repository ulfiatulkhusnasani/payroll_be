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
        'jam_pulang',       // Jam pulang kerja
        'foto_pulang',      // Foto saat absensi pulang
        'latitude_pulang',  // Lokasi (latitude) saat absensi pulang
        'longitude_pulang', // Lokasi (longitude) saat absensi pulang
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

    // Akses foto pulang dalam bentuk base64
    public function getFotopulangBase64Attribute()
    {
        if ($this->foto_pulang) {
            return base64_encode(Storage::disk('public')->get($this->foto_pulang));
        }
        return null;
    }

    // Relasi dengan tabel karyawans
    public function karyawan()
    {
        return $this->belongsTo(Karyawan::class, 'id_karyawan');
    }
}