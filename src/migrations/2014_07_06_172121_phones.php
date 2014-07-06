<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class Phones extends Migration
{
    public function up()
    {
        Schema::create('phones', function ($table) {
            $table->increments('id');
            $table->string('label', 20);
            $table->string('number', 20);
            $table->integer('phone_type_id')->unsigned();
            $table->foreign('phone_type_id')->references('id')->on('phones_types');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('phones_types', function ($table) {
            $table->increments('id');
            $table->string('name', 20);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('phones');
        Schema::drop('phones_types');
    }
}
