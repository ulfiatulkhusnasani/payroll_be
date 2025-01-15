<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dinas_luar_kota', function (Blueprint $table) {
            $table->enum('status', ['disetujui', 'ditolak', 'pending'])->default('pending');
        });
    }

    public function down()
    {
        Schema::table('dinas_luar_kota', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};