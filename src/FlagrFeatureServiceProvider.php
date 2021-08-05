<?php

namespace Sidekicker\FlagrFeatureLaravel;

use Flagr\Client\Api\ConstraintApi;
use Flagr\Client\Api\EvaluationApi;
use Flagr\Client\Api\FlagApi;
use Flagr\Client\Api\TagApi;
use GuzzleHttp\Client;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FlagrFeatureServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('flagr-feature-laravel')
            ->hasCommand(CreateFlagCommand::class)
            ->hasConfigFile(['flagr-feature']);
    }

    public function packageRegistered(): void
    {
        $this->registerClasses();
    }

    protected function createGuzzleClient(): Client
    {
        return new Client([
            'base_uri' => config('flagr-feature.flagr_url'),
            'connect_timeout' => config('flagr-feature.connect_timeout'),
            'timeout' => config('flagr-feature.timeout'),
        ]);
    }

    protected function registerClasses(): void
    {
        $this->app->bind(Feature::class, function () {
            return new Feature(
                new EvaluationApi(
                    client: $this->createGuzzleClient()
                )
            );
        });

        $this->app->alias(Feature::class, 'feature');

        $this->app->bind(ConstraintApi::class, function () {
            return new ConstraintApi(
                client: $this->createGuzzleClient()
            );
        });

        $this->app->bind(FlagApi::class, function () {
            return new FlagApi(
                client: $this->createGuzzleClient()
            );
        });

        $this->app->bind(TagApi::class, function () {
            return new TagApi(
                client: $this->createGuzzleClient()
            );
        });
    }
}