<?php
use Illuminate\Database\Seeder as Seeder;

class PostsSeeder extends Seeder
{
    public function run()
    {
        Post::Create(['title' => 'Do you like PHP?', 'content' => 'This is my first post']);
        Post::Create(['title' => 'Do you like JS?', 'content' => 'Second post :)']);
    }
}
