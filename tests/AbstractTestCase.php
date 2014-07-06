<?php

abstract class AbstractTestCase extends Orchestra\Testbench\TestCase
{
    public function setUp()
    {
        parent::setUp();

        $artisan = $this->app->make('artisan');

        // migrate packages
        // laravel only run migrations in src folder (dont know why)
        // @todo I want this out of composer.json. How can I do?
        $artisan->call('migrate', [
            '--path' => '../src/migrations',
            '--database' => 'testbench',
        ]);

        $artisan->call('db:seed', [
            '--class' => 'Seed',
        ]);
    }

    protected function getEnvironmentSetUp($app)
    {
        // reset base path to point to our package's src directory
        $app['path.base'] = __DIR__ . '/../src';

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }
}
