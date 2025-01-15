<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('izin', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('karyawans'); // relasi dengan tabel karyawans
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->string('alasan'); // Tambahkan kolom alasan
            $table->string('keterangan');
            $table->integer('durasi'); // Tambahkan kolom durasi
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('izin');
    }
};
