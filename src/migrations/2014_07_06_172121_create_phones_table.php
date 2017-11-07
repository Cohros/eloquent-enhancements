<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePhonesTable extends Migration
{
    public function up()
    {
        Schema::create('phones_types', function (Blueprint $table) {
            $table->increments('id_phone_type');
            $table->string('name', 20);
            $table->timestamps();
        });

        Schema::create('phones', function (Blueprint $table) {
            $table->increments('id_phone');
            $table->string('label', 20);
            $table->string('number', 20);
            $table->integer('id_phone_type')->unsigned();
            $table->foreign('id_phone_type')->references('id_phone_type')->on('phones_types');
            $table->integer('id_user')->unsigned();
            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }
}
