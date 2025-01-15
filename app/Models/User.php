<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    // Kolom yang bisa diisi secara mass-assignment
    protected $fillable = [
        'nama_karyawan',
        'email',
        'password',
    ];

    // Kolom yang disembunyikan dari serialisasi, seperti API response
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Mengatur casting tipe data untuk kolom tertentu
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}