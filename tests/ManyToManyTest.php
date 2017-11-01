<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\MessageBag;

class ManyToMany extends AbstractTestCase
{
    public function testAddingRelatedWithoutRelationshipModel()
    {
        $input = [
            'name' => 'São Paulo',
            'cities' => [
                ['id_city' => City::whereName('São Paulo')->first()->id_city],
                ['id_city' => City::whereName('Taboão da Serra')->first()->id_city],
            ],
        ];

        $region = new Region;
        $this->assertTrue($region->createAll($input));

        $region = Region::with('cities')->find($region->id_region);
        $this->assertEquals(2, count($region->cities));
    }

    public function testAddingObjectsRelatedWithoutRelationshipModel()
    {
        $data = ['name' => 'Ribeirão'];
        $city = new City;
        $this->assertTrue($city->saveAll($data));
        $city = City::find($city->id_city);

        $data = ['name' => 'Cravinhos'];
        $city2 = new City;
        $this->assertTrue($city2->saveAll($data));
        $city2 = City::find($city2->id_city);

        $input = [
            'name' => 'region_x',
            'cities' => [
                $city->toArray(),
                $city2->toArray(),
                ['name' => 'Bonfim'],
            ]
        ];

        $region = new Region;
        $this->assertTrue($region->createAll($input));

        $region = Region::find($region->id_region);
        $this->assertEquals(3, count($region->cities));
    }

    public function testRemoveRelated()
    {
        $input = [
            'name' => 'São Paulo',
            'cities' => [
                ['id_city' => City::whereName('São Paulo')->first()->id_city],
                ['id_city' => City::whereName('Taboão da Serra')->first()->id_city],
            ],
        ];

        $region = new Region;
        $this->assertTrue($region->createAll($input));

        $region = Region::find($region->id_region);
        $this->assertEquals(2, count($region->cities));

        $this->assertTrue($region->saveAll(['cities' => [
            '_delete' => true, 'id_city' => City::whereName('São Paulo')->first()->id_city]
        ]));

        $region = Region::find($region->id_region);
        $this->assertEquals(1, count($region->cities));
    }

    public function testAddAndCreateRelatedWithoutRelationshipModel()
    {
        $input = [
            'name' => 'São Paulo',
            'cities' => [
                ['name' => 'Test city', '_create' => true],
            ],
        ];

        $region = new Region;
        $this->assertTrue($region->createAll($input));

        $region = Region::find($region->id_region);
        $this->assertEquals(1, count($region->cities));
    }

    public function testShouldNotSaveAllWithErrors()
    {
        $input = ['name' => 'Barrinha'];
        $region = new Region;
        $this->assertTrue($region->saveAll($input));
        $this->assertFalse($region->saveAll(['name' => '']));

        $region = Region::find($region->id_region);
        $this->assertEquals($input['name'], $region->name);
    }

    public function testShouldSaveWithRelationshipModel()
    {
        $post = Post::find(1);
        $input = $post->toArray();
        $input['authors'] = [
            ['id_user' => 1, 'main' => 1],
            ['id_user' => 2, 'main' => 0],
        ];

        $this->assertTrue($post->saveAll($input));

        $post = Post::find(1);
        $this->assertEquals(2, count($post->authors));
    }

    public function testShouldUseSync()
    {
        $input = [
            'title' => 'Post x',
            'content' => 'Content x',
            'authors' => [
                'id_user' => [1, 2],
            ]
        ];

        $post = new Post;
        $this->assertTrue($post->createAll($input));
        $this->assertEquals(2, $post->authors->count());
    }

    public function testShoulSaveBelongsToManyWithRelationshipModel()
    {
        $data = [
            'name' => 'area_x',
            'cities' => [
                'id_city' => 1,
            ]
        ];

        $area = new Area;
        $this->assertTrue($area->saveAll($data));
        $area = Area::with('cities')->find(1);
        $this->assertEquals(1, $area->cities->first()->id_city);

        define('xpto', true);
        $this->assertTrue($area->saveAll([
            'id' => 1,
            'name' => 'area_y',
            'cities' => [
                'id_area_city' => $area->cities->first()->pivot->id_area_city,
                'id_city' => 2,
                'id_area' => 1,
            ]
        ]));
        $area = Area::with('cities')->find(1);
        $this->assertEquals(2, $area->cities->first()->id_city);
    }

    public function testShoulSaveBelongsToManyIfRelatedObjectIsProvided()
    {
        $data = ['name' => 'Ribeirão'];
        $city = new City;
        $this->assertTrue($city->saveAll($data));
        $city = City::find($city->id_city);

        $data = ['name' => 'Cravinhos'];
        $city2 = new City;
        $this->assertTrue($city2->saveAll($data));
        $city2 = City::find($city2->id_city);

        $data = [
            'name' => 'area_x',
            'cities' => [
                $city->toArray(),
                $city2->toArray(),
            ]
        ];

        $area = new Area;
        $this->assertTrue($area->saveAll($data));
        $area = Area::with('cities')->find($area->id_area);
        $this->assertEquals($area->cities->first()->name, 'Ribeirão');

        $data = ['name' => 'Sertaozinho'];
        $city = new City;
        $this->assertTrue($city->saveAll($data));
        $city = City::find($city->id_city);

        $area = new Area;
        $data = [
            'name' => 'area_x_v2',
            'cities' => [
                $city->toArray(),
                $city2->toArray(),
            ]
        ];

        $this->assertTrue($area->saveAll($data));
        $area = Area::with('cities')->find($area->id_area);
        $this->assertEquals($area->cities->first()->name, 'Sertaozinho');
        $this->assertEquals(2, count($area->cities));

    }
}
