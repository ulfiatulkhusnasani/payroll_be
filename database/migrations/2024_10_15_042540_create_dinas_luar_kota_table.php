<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('dinas_luar_kota', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_karyawan')->constrained('karyawans')->onDelete('cascade');
            $table->date('tgl_berangkat');
            $table->date('tgl_kembali');
            $table->string('kota_tujuan', 255);
            $table->string('keperluan', 255);
            $table->decimal('biaya_transport', 10, 2);
            $table->decimal('biaya_penginapan', 10, 2);
            $table->decimal('uang_harian', 10, 2);
            $table->decimal('total_biaya', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dinas_luar_kota');
    }
};