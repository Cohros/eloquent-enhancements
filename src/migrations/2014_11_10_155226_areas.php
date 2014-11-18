<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class Areas extends Migration
{
    public function up()
    {
        Schema::create('areas', function ($table) {
            $table->increments('id');
            $table->string('name', 130);
            $table->timestamps();
        });

        Schema::create('areas_cities', function ($table) {
            $table->increments('id');
            $table->integer('area_id')->unsigned();
            $table->foreign('area_id')->references('id')->on('areas');
            $table->integer('city_id')->unsigned();
            $table->foreign('city_id')->references('id')->on('cities');
            $table->timestamps();
        });
    }
}
