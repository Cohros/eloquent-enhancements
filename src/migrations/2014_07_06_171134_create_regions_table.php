<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRegionsTable extends Migration
{
    public function up()
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->increments('id_region');
            $table->string('name', 130);
            $table->timestamps();
        });

        Schema::create('regions_cities', function (Blueprint $table) {
            $table->increments('id_region_city');
            $table->integer('id_region')->unsigned();
            $table->foreign('id_region')->references('id_region')->on('regions');
            $table->integer('id_city')->unsigned();
            $table->foreign('id_city')->references('id_city')->on('cities');
        });
    }
}
