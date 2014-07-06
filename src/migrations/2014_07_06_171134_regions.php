<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class Regions extends Migration
{
    public function up()
    {
        Schema::create('regions', function ($table) {
            $table->increments('id');
            $table->string('name', 130);
            $table->timestamps();
        });

        Schema::create('regions_cities', function ($table) {
            $table->increments('id');
            $table->integer('region_id')->unsigned();
            $table->foreign('region_id')->references('id')->on('regions');
            $table->integer('city_id')->unsigned();
            $table->foreign('city_id')->references('id')->on('cities');
        });
    }

    public function down()
    {
        Schema::drop('regions');
    }
}
