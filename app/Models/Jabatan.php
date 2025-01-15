<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    use HasFactory;

    protected $table = 'jabatan'; // Nama tabel di database

    // Kolom yang dapat diisi melalui mass assignment
    protected $fillable = [
        'jabatan',
        'gaji_pokok',
        'uang_kehadiran_perhari',
        'uang_makan',
        'bonus',
        'tunjangan',
        'potongan'
    ];
}