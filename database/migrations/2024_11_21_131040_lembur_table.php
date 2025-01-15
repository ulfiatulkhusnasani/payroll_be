<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lembur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('karyawans')->onDelete('cascade');
            $table->date('tanggal_lembur');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->integer('durasi_lembur')->nullable(); // Dalam menit/jam
            $table->text('alasan_lembur')->nullable();
            $table->timestamps();
        });
      
    }

    public function down()
    {
        Schema::dropIfExists('lembur');
    }
};