<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gaji_karyawans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_karyawan'); // Nama Karyawan
            $table->integer('hadir')->default(0); // Jumlah hadir
            $table->integer('cuti')->default(0); // Jumlah cuti
            $table->integer('lembur')->default(0); // Jam lembur
            $table->integer('dinas_keluar_kota')->default(0); // Hari dinas keluar kota
            $table->decimal('potongan', 15, 2)->default(0); // Potongan gaji
            $table->decimal('gaji_pokok', 15, 2)->default(0); // Gaji pokok
            $table->timestamps(); // Tanggal dibuat dan diperbarui
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gaji_karyawans');
    }
};
