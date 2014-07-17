<?php

class OneToManyTest extends AbstractTestCase
{
    public function testCreateOneRecord()
    {
        $luis = [
            'name' => 'Luís Henrique Faria',
            'email' => 'luish.faria@gmail.com',
            'password' => '12300',
            'password_confirmation' => '12300',
        ];

        $this->assertTrue(with(new User)->createAll($luis));
    }

    public function testCreateWithRelationships()
    {
        $input = [
            'name' => 'Luís Henrique Faria',
            'email' => 'luish.faria@gmail.com',
            'phones' => [
                ['number' => '1111111111', 'label' => 'cel', 'phone_type_id' => 1],
                ['number' => '111114441', 'label' => 'cel 2', 'phone_type_id' => 1]
            ]
        ];

        $this->assertTrue(with(new User)->createAll($input));
        $luis = User::whereEmail('luish.faria@gmail.com')->with('phones')->first();
        $this->assertEquals(2, count($luis->phones));
    }

    public function testEditRelationships()
    {
        $input = [
            'name' => 'Luís Henrique Faria',
            'email' => 'luish.faria@gmail.com',
            'phones' => [
                ['number' => '1111111111', 'label' => 'cel', 'phone_type_id' => 1],
                ['number' => '111114441', 'label' => 'cel 2', 'phone_type_id' => 1]
            ]
        ];

        $this->assertTrue(with(new User)->createAll($input));
        $luis = User::whereEmail('luish.faria@gmail.com')->with('phones')->first();

        $input['name'] = 'Luís';
        $input['email'] = 'luque@luque.cc';
        $input['phones'][0]['id'] = $luis->phones[0]->id;
        $input['phones'][0]['number'] = '000000';

        $input['phones'][1]['id'] = $luis->phones[1]->id;
        $input['phones'][1]['number'] = '111111';
        $input['phones'][1]['phone_type_id'] = 2;

        $this->assertTrue($luis->saveAll($input));

        $luis = User::whereEmail('luque@luque.cc')->with('phones')->first();
        $this->assertEquals(2, count($luis->phones));
        $this->assertEquals($input['name'], $luis->name);
        $this->assertEquals($input['phones'][0]['number'], $luis->phones[0]->number);
        $this->assertEquals($input['phones'][1]['number'], $luis->phones[1]->number);
        $this->assertEquals($input['phones'][1]['phone_type_id'], $luis->phones[1]->phone_type_id);
    }

    public function testIgnoringEmptyData()
    {
        $input = [
            'name' => 'Luís Henrique Faria',
            'email' => 'luish.faria@msn.com',
            'phones' => [
                ['number' => '', 'label' => '', 'phone_type_id' => ''],
                ['number' => '111114441', 'label' => 'cel 2', 'phone_type_id' => 1],
            ]
        ];

        $this->assertTrue(with(new User)->createAll($input));
        $object = User::whereEmail('luish.faria@msn.com')->with('phones')->first();
        $this->assertEquals(1, count($object->phones));
    }

    public function testTransportingErrors()
    {
        $input = [
            'name' => 'Luís Henrique Faria',
            'email' => 'luish.faria@gmail.com',
            'phones' => [
                ['number' => '1111111111', 'label' => 'cel', 'phone_type_id' => 1],
                ['number' => '111114441', 'label' => 'cel 2', 'phone_type_id' => 1],
            ]
        ];

        $object = new User;
        $testInput = $input;
        unset($testInput['name']);
        $this->assertFalse($object->createAll($testInput));
        $this->assertTrue($object->errors()->has('name'));

        $object = new User;
        $testInput = $input;
        $testInput['email'] = 'xxx@gmail.com';
        unset($testInput['phones'][1]['number']);
        \DB::beginTransaction();
        $this->assertFalse($object->createAll($testInput));
        $this->assertEquals(1, count($object->errors()->has('phones.1.number')));
        \DB::rollback();

        $this->assertEquals(0, User::whereEmail('xxx@gmail.com')->count());
    }
}
