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
        Schema::create('absensi', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->foreignId('id_karyawan')->constrained('karyawans'); // ID Karyawan dengan foreign key ke tabel karyawans
            $table->date('tanggal'); // Tanggal Absensi
            $table->time('jam_masuk')->nullable(); // Waktu masuk
            $table->longText('foto_masuk')->nullable();
            $table->decimal('latitude_masuk', 10, 7)->nullable(); // Latitude
            $table->decimal('longitude_masuk', 10, 7)->nullable(); // Longitude
            $table->string('status'); // Status Kehadiran (Terlambat, Tepat waktu)
            $table->timestamps(); // created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('absensi');
    }
};