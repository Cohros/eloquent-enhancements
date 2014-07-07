<?php
use Illuminate\Database\Seeder as Seeder;

class Seed extends Seeder
{
    public function run()
    {
        $this->call('CitySeeder');
        $this->call('RegionSeeder');
        $this->call('PhoneTypeSeeder');
        $this->call('UsersSeeder');
        $this->call('PostsSeeder');
    }
}
