<?php

use Illuminate\Database\Migrations\Migration;

class CreateCarsTable extends Migration
{
    public function up()
    {
        Schema::create('cars', function ($table) {
            $table->increments('id_car');
            $table->string('vendor', 130);
            $table->string('model', 100);
            $table->integer('id_user')->unsigned();
            $table->foreign('id_user')->references('id_user')->on('users');
            $table->timestamps();
        });
    }
}
