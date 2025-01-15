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
        Schema::table('absensi', function (Blueprint $table) {
            if (!Schema::hasColumn('absensi', 'id_karyawan')) {
                $table->unsignedBigInteger('id_karyawan')->after('id')->nullable();
                $table->foreign('id_karyawan')
                    ->references('id')
                    ->on('karyawans')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('absensi', 'jam_keluar')) {
                $table->time('jam_keluar')->nullable()->after('longitude_masuk');
            }

            if (!Schema::hasColumn('absensi', 'foto_keluar')) {
                $table->longText('foto_keluar')->nullable()->after('jam_keluar');
            }

            if (!Schema::hasColumn('absensi', 'latitude_keluar')) {
                $table->decimal('latitude_keluar', 10, 7)->nullable()->after('foto_keluar');
            }

            if (!Schema::hasColumn('absensi', 'longitude_keluar')) {
                $table->decimal('longitude_keluar', 10, 7)->nullable()->after('latitude_keluar');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('absensi', function (Blueprint $table) {
            // Hapus kolom hanya jika ada
            if (Schema::hasColumn('absensi', 'id_karyawan')) {
                $table->dropForeign(['id_karyawan']);
                $table->dropColumn('id_karyawan');
            }

            if (Schema::hasColumn('absensi', 'jam_keluar')) {
                $table->dropColumn('jam_keluar');
            }

            if (Schema::hasColumn('absensi', 'foto_keluar')) {
                $table->dropColumn('foto_keluar');
            }

            if (Schema::hasColumn('absensi', 'latitude_keluar')) {
                $table->dropColumn('latitude_keluar');
            }

            if (Schema::hasColumn('absensi', 'longitude_keluar')) {
                $table->dropColumn('longitude_keluar');
            }
        });
    }
};