<?php

namespace DutchBridge\KlaviyoForLaravel\Test;

use DutchBridge\KlaviyoForLaravel\KlaviyoForLaravelServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        config([
            'klaviyo.enabled' => true,
            'klaviyo.endpoint' => 'https://a.klaviyo.com/api/',
            'klaviyo.api_version' => '2024-05-15',
            'klaviyo.private_api_key' => 'test-private-key',
            'klaviyo.public_api_key' => 'test-public-key',
            'klaviyo.identity_key_name' => 'email',
            'klaviyo.queue' => 'klaviyo',
            'klaviyo.session_key' => '_klaviyo',
            'klaviyo.identify_on_login' => true,
        ]);
    }

    protected function getPackageProviders($app): array
    {
        return [
            KlaviyoForLaravelServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Klaviyo' => \DutchBridge\KlaviyoForLaravel\Klaviyo::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}