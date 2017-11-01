<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateAreasTable extends Migration
{
    public function up()
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->increments('id_area');
            $table->string('name', 130);
            $table->timestamps();
        });

        Schema::create('areas_cities', function (Blueprint $table) {
            $table->increments('id_area_city');
            $table->integer('id_area')->unsigned();
            $table->foreign('id_area')->references('id_area')->on('areas');
            $table->integer('id_city')->unsigned();
            $table->foreign('id_city')->references('id_city')->on('cities');
            $table->timestamps();
        });
    }
}
