<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePostsTable extends Migration
{
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id_post');
            $table->string('title', 60)->unique();
            $table->string('content', 160);
            $table->timestamps();
        });

        Schema::create('posts_authors', function (Blueprint $table) {
            $table->increments('id_post_author');
            $table->integer('id_user')->unsigned();
            $table->foreign('id_user')->references('id_user')->on('users')->onDelete('cascade');
            $table->integer('id_post')->unsigned();
            $table->foreign('id_post')->references('id_post')->on('posts')->onDelete('cascade');
            $table->boolean('main')->nullable()->default(false);
            $table->timestamps();
        });
    }
}
