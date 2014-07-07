<?php
use Illuminate\Database\Seeder as Seeder;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $bob = User::create(['name' => 'Bob', 'email' => 'bob@thats70show.com']);
        $eric = User::create(['name' => 'Eric', 'email' => 'eric@thats70show.com']);
    }
}
