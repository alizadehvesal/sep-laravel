<?php

declare(strict_types=1);

namespace Vestra\Sep;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Support\ServiceProvider;
use Vestra\Sep\Contracts\PaymentRepository;
use Vestra\Sep\Contracts\SepHttpClient;
use Vestra\Sep\Services\DatabasePaymentRepository;
use Vestra\Sep\Services\SepGateway;
use Vestra\Sep\Services\SepHttpClientImpl;
use Vestra\Sep\Services\SepPaymentService;

final class SepServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sep.php', 'sep');

        $this->app->singleton(ClientInterface::class, function (): ClientInterface {
            return new Client();
        });

        $this->app->singleton(SepHttpClient::class, function ($app): SepHttpClient {
            return new SepHttpClientImpl(
                $app->make(ClientInterface::class),
                (int) config('sep.timeout', 15),
                (int) config('sep.connect_timeout', 5),
                (string) config('sep.user_agent', 'Vestra-Laravel-SEP/1.0'),
            );
        });

        $this->app->singleton(SepGateway::class, function ($app): SepGateway {
            return new SepGateway(
                $app->make(SepHttpClient::class),
                $app->make('url'),
                (string) config('sep.terminal_id', ''),
                (array) config('sep.urls'),
                (int) config('sep.token_expiry_minutes', 20),
            );
        });

        $this->app->singleton(PaymentRepository::class, function (): PaymentRepository {
            return new DatabasePaymentRepository((string) config('sep.database.table', 'sep_payments'));
        });

        $this->app->singleton(SepPaymentService::class, function ($app): SepPaymentService {
            return new SepPaymentService(
                $app->make(SepGateway::class),
                $app->make(PaymentRepository::class),
                $app->make('db'),
                (int) config('sep.verify_retries', 3),
                (int) config('sep.verify_retry_delay_ms', 1000),
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/sep.php' => config_path('sep.php'),
        ], 'sep-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'sep-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/sep'),
        ], 'sep-views');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'sep');

        if (config('sep.publish_routes', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/sep.php');
        }
    }
}
