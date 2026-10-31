<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Eamirgh\SnappPay\Facades\SnappPay;
use Eamirgh\SnappPay\SnappPayServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SnappPayServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'SnappPay' => SnappPay::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('snapppay.default', 'default');
        $app['config']->set('snapppay.connections.default', [
            'base_url' => 'https://api.snapppay.test',
            'client_id' => 'test_client_id',
            'client_secret' => 'test_client_secret',
            'username' => 'test_username',
            'password' => 'test_password',
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        $app['config']->set('snapppay.cache_token', false);
    }
}
