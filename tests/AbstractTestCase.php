<?php

abstract class AbstractTestCase extends Orchestra\Testbench\TestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $artisan = $this->app->make('artisan');
        } catch (Exception $ex) {
            $artisan = $this->app->make('Artisan');
        }

        // migrate packages
        // laravel only run migrations in src folder (dont know why)
        // @todo I want this out of composer.json. How can I do?
        \Artisan::call('migrate', [
            '--path' => '../../../../src/migrations',
            '--database' => 'testbench',
        ]);

        \Artisan::call('db:seed', [
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
