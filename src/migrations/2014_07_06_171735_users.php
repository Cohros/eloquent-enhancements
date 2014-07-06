<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class Users extends Migration
{
    public function up()
    {
        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('name', 60);
            $table->string('email', 160)->unique();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::drop('users');
    }
}
