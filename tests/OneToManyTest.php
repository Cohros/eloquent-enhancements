<?php

class OneToManyTest extends AbstractTestCase
{
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

    public function testShouldApplyLimitOnRelationship()
    {
        $input = [
            'name' => 'User User',
            'email' => 'user@domain.tld',
            'phones' => [
                ['number' => '111111', 'label' => 'phone a', 'phone_type_id' => 1],
                ['number' => '222222', 'label' => 'phone b', 'phone_type_id' => 1],
                ['number' => '333333', 'label' => 'phone c', 'phone_type_id' => 1],
                ['number' => '444444', 'label' => 'phone d', 'phone_type_id' => 1],
            ]
        ];

        $user = new User;
        \DB::beginTransaction();
        $this->assertFalse($user->createAll($input));
        $this->assertTrue($user->errors()->has('phones'));
        \DB::rollback();
        
        $input = [
            'name' => 'User',
            'email' => 'user@domain.otld',
            'phones' => [],
        ];
        $user = new User;
        \DB::beginTransaction();
        $this->assertFalse($user->createAll($input));
        $this->assertTrue($user->errors()->has('phones'));
        \DB::rollback();
        
        $input = [
            'name' => 'User',
            'email' => 'user@domain.otld',
        ];
        $user = new User;
        \DB::beginTransaction();
        $this->assertFalse($user->createAll($input));
        $this->assertTrue($user->errors()->has('phones'));
        \DB::rollback();

        $input = [
            'name' => 'User User',
            'email' => 'user@domain.tld',
            'phones' => [
                ['number' => '111111', 'label' => 'phone a', 'phone_type_id' => 1],
                ['number' => '222222', 'label' => 'phone b', 'phone_type_id' => 1],
            ]
        ];
        $user = new User;
        \DB::beginTransaction();
        $this->assertTrue($user->createAll($input));
        $user->load('phones');
        $this->assertTrue($user->saveAll($user->toArray()));
        \DB::commit();
        
    }

    public function testShouldSaveRelationshipsWithNonSequentialArrayKeys()
    {
        $input = [
            'name' => 'User User',
            'email' => 'user@domain.tld',
            'phones' => [
                1 => ['number' => '111111', 'label' => 'phone a', 'phone_type_id' => 1],
                3 => ['number' => '222222', 'label' => 'phone b', 'phone_type_id' => 1],
            ]
        ];

        $user = new User;
        $this->assertTrue($user->createAll($input));
        $user = User::whereEmail('user@domain.tld')->with('phones')->first();
        $this->assertEquals(2, count($user->phones));
    }
    
    public function testShouldCreateParent()
    {
        $input = [
            'name' => 'Steve',
            'email' => 'steve@monster.com',
            'phones' => array (
                array (
                    'number' => '1111111111',
                    'label' => 'phone x',
                    'type' => array (
                        'name' => 'phone type x',
                    )
                )
            ),
        ];
        
        $user = new User;
        $this->assertTrue($user->createAll($input));
    }
    
    public function testShouldNotCreateIfFailsToCreateBelongsTo()
    {
        $input = [
            'name' => 'Steve',
            'email' => 'steve@monster.com',
            'phones' => array (
                array (
                    'number' => '1111111111',
                    'label' => 'phone x',
                    'type' => array (
                        'name' => '',
                    )
                )
            ),
        ];
        
        $user = new User;
        $this->assertFalse($user->createAll($input));
        $this->assertTrue($user->errors()->has('phones.0.type.name'));
    }
}
