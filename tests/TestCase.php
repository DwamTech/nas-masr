<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $this->forceIsolatedTestingDatabase($app);

        return $app;
    }

    protected function forceIsolatedTestingDatabase(Application $app): void
    {
        if (! $app->environment('testing')) {
            throw new RuntimeException('Tests must run under the testing environment only.');
        }

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.foreign_key_constraints', true);

        $default = (string) $app['config']->get('database.default');
        $database = (string) $app['config']->get('database.connections.sqlite.database');

        if ($default !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException('Unsafe test database configuration detected. Tests are blocked to protect local data.');
        }

        $app['db']->purge();
        $app['db']->setDefaultConnection('sqlite');
        $app['db']->purge('sqlite');
    }
}
