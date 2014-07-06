<?php

use Illuminate\Database\Seeder as Seeder;

class CitySeeder extends Seeder
{
    public function run()
    {
        $cities = [
            ['name' => 'Ribeirão Preto'],
            ['name' => 'Jardinópolis'],
            ['name' => 'Serrana'],
            ['name' => 'Cravinhos'],
            ['name' => 'Bonfim Paulista'],
            ['name' => 'Sertãozinho'],
            ['name' => 'Barrinha'],
            ['name' => 'Dumont'],
            ['name' => 'Jaboticabal'],
            ['name' => 'Pradópolis'],
            ['name' => 'São Paulo'],
            ['name' => 'São Caetado do Sul'],
            ['name' => 'Taboão da Serra'],
            ['name' => 'Guarulhos'],
            ['name' => 'Barueri'],
            ['name' => 'Mauá'],
            ['name' => 'Cotia'],
            ['name' => 'Osasco'],
            ['name' => 'Itaquaquecetuba'],
            ['name' => 'Suzano'],
            ['name' => 'Curitiba'],
            ['name' => 'São José dos Pinhais'],
            ['name' => 'Araucária'],
            ['name' => 'Piraquara'],
            ['name' => 'Colombo'],
            ['name' => 'Campo Largo'],
            ['name' => 'Balsa Nova'],
            ['name' => 'Contenda'],
            ['name' => 'Campo Largo'],
        ];

        foreach ($cities as $city) {
            City::create($city);
        }
    }
}
