<?php
use Illuminate\Database\Seeder as Seeder;

class PhoneTypeSeeder extends Seeder
{
    public function run()
    {
        $phone = PhoneType::create(['name' => 'Phone']);
        $celphone = PhoneType::create(['name' => 'Celphone']);
    }
}
