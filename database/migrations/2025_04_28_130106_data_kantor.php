<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('data_kantor', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kantor');
            $table->string('latitude_kantor');
            $table->string('longitude_kantor');
            $table->time('jam_masuk');
            $table->time('jam_pulang');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('data_kantor');
    }
};
