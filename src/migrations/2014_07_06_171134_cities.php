<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class Cities extends Migration
{
    public function up()
    {
        Schema::create('cities', function ($table) {
            $table->increments('id');
            $table->string('name', 130);
            $table->timestamps();
        });
    }
}
