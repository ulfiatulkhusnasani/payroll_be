<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id('id_tugas'); // Primary key untuk ID Tugas
            $table->foreignId('id_karyawan')->constrained('karyawans'); // ID Karyawan sebagai foreign key ke tabel karyawans
            $table->string('judul_proyek'); // Nama proyek
            $table->text('kegiatan'); // Deskripsi kegiatan
            $table->enum('status', ['belum dimulai', 'dalam progres', 'selesai'])->default('belum dimulai'); // Status tugas
            $table->date('tgl_mulai'); // Tanggal mulai tugas
            $table->date('batas_penyelesaian'); // Tanggal batas penyelesaian
            $table->date('tgl_selesai')->nullable(); // Tanggal selesai (nullable karena tugas bisa saja belum selesai)
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};