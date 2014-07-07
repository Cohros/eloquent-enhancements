<?php

class ErrorsTest extends AbstractTestCase
{
    public function testErrors()
    {
        $userModel = new User;
        $this->assertTrue($userModel->errors() instanceof Illuminate\Support\MessageBag);

        $userModel->errors()->add('test', 'test');
        $this->assertEquals('test', $userModel->errors()->first('test'));

        $userModel = new User;
        $userModel->setErrors(new Illuminate\Support\MessageBag([]));
        $this->assertTrue($userModel->errors() instanceof Illuminate\Support\MessageBag);
    }
}
