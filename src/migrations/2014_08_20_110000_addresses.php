<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class Addresses extends Migration
{
    public function up()
    {
        Schema::create('addresses', function ($table) {
            $table->increments('id');
            $table->string('address', 256);
            $table->string('postal_code', 10)->nullable();
            $table->integer('addressable_id')->unsigned();
            $table->string('addressable_type', 256);
            $table->timestamps();
        });
    }
}
