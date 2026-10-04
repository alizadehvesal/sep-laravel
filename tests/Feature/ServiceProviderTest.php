<?php

declare(strict_types=1);

namespace Vestra\Sep\Tests\Feature;

use Orchestra\Testbench\TestCase;
use Vestra\Sep\Contracts\SepHttpClient;
use Vestra\Sep\Services\SepGateway;
use Vestra\Sep\SepServiceProvider;

final class ServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [SepServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('sep.terminal_id', '2015');
    }

    public function test_gateway_is_registered(): void
    {
        self::assertInstanceOf(SepGateway::class, $this->app->make(SepGateway::class));
        self::assertInstanceOf(SepHttpClient::class, $this->app->make(SepHttpClient::class));
    }
}
