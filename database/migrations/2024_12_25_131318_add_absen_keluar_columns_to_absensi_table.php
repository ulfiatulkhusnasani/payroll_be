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

            if (!Schema::hasColumn('absensi', 'jam_pulang')) {
                $table->time('jam_pulang')->nullable()->after('longitude_masuk');
            }

            if (!Schema::hasColumn('absensi', 'foto_pulang')) {
                $table->longText('foto_pulang')->nullable()->after('jam_pulang');
            }

            if (!Schema::hasColumn('absensi', 'latitude_pulang')) {
                $table->decimal('latitude_pulang', 10, 7)->nullable()->after('foto_pulang');
            }

            if (!Schema::hasColumn('absensi', 'longitude_pulang')) {
                $table->decimal('longitude_pulang', 10, 7)->nullable()->after('latitude_pulang');
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

            if (Schema::hasColumn('absensi', 'jam_pulang')) {
                $table->dropColumn('jam_pulang');
            }

            if (Schema::hasColumn('absensi', 'foto_pulang')) {
                $table->dropColumn('foto_pulang');
            }

            if (Schema::hasColumn('absensi', 'latitude_pulang')) {
                $table->dropColumn('latitude_pulang');
            }

            if (Schema::hasColumn('absensi', 'longitude_pulang')) {
                $table->dropColumn('longitude_pulang');
            }
        });
    }
};