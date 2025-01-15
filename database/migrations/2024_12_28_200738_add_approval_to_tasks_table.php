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
        Schema::table('tasks', function (Blueprint $table) {
            $table->integer('point')->default(0)->after('tgl_selesai'); // Kolom point dengan nilai default 0
            $table->enum('status_approval', ['pending', 'disetujui', 'ditolak'])->default('pending')->after('point'); // Kolom status approval dengan default pending
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['point', 'status_approval']); // Menghapus kolom point dan status_approval
        });
    }
};
