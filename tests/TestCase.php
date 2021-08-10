<?php

namespace Sidekicker\FlagrFeature\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Sidekicker\FlagrFeature\FlagrFeatureServiceProvider;

class TestCase extends TestbenchTestCase
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [FlagrFeatureServiceProvider::class];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        config()->set('flagr-feature.flagr_url', env('FEATURE_FLAGR_URL'));
        config()->set('flagr-feature.auth', 'basic');
        config()->set('flagr-feature.basic.username', 'flagr');
        config()->set('flagr-feature.basic.password', 'flagr');
        $user = $this->partialMock(Authenticatable::class);
        $user->shouldReceive('jsonEncode')->andReturn([
            'id' => 1,
            'username' => 'user'
        ]);
        $this->actingAs($user);
    }
}
