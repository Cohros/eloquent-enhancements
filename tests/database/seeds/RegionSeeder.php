<?php
use Illuminate\Database\Seeder as Seeder;

class RegionSeeder extends Seeder
{
    public function run()
    {
        $region = Region::create(['name' => 'Ribeirão Preto']);
        $region->cities()->sync([1,2,3,4,5]);
    }
}
