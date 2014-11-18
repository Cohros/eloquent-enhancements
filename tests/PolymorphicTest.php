<?php

class PolymorphicTest extends AbstractTestCase
{
    public function testShouldSaveAddress()
    {
        $input = array (
            'name' => 'Model Person',
            'email' => 'person@gmail.com',
            'phones' => array (
                ['number' => '111111', 'label' => 'phone a', 'phone_type_id' => 1],
            ),
            'addresses' => array (
                ['address' => 'Model Address', 'postal_code' => '999999'],
            ),
        );
        
        $user = new User();
        $this->assertTrue($user->createAll($input));
        
        $user = User::whereEmail('person@gmail.com')->with('addresses')->first();
        $this->assertEquals(1, $user->addresses->count());
    }
}
