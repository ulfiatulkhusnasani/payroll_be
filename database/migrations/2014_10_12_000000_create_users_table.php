<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menjalankan migrasi.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Primary key auto increment
            $table->string('nama_karyawan'); // Nama karyawan
            $table->string('email')->unique(); // Email yang unik
            $table->string('password'); // Kolom password
            $table->rememberToken(); // Token untuk fitur "remember me"
            $table->timestamps(); // Timestamps untuk created_at dan updated_at
        });
    }

    /**
     * Membatalkan migrasi.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};