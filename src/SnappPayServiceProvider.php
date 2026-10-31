<?php

declare(strict_types=1);

namespace Eamirgh\SnappPay;

use Illuminate\Support\ServiceProvider;
use Eamirgh\SnappPay\Contracts\SnappPayClientInterface;
use Eamirgh\SnappPay\Contracts\SnappPayGateway;
use Eamirgh\SnappPay\Contracts\TokenManagerInterface;
use Eamirgh\SnappPay\Services\SnappPayClient;
use Eamirgh\SnappPay\Services\SnappPayManager;
use Eamirgh\SnappPay\Services\SnappPayService;

class SnappPayServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/snapppay.php', 'snapppay');

        $this->app->singleton('snapppay', function ($app) {
            return new SnappPayManager($app);
        });

        $this->app->alias('snapppay', SnappPayManager::class);
        $this->app->alias('snapppay', SnappPayGateway::class);

        $this->app->bind(SnappPayGateway::class, function ($app) {
            return $app->make('snapppay');
        });

        $this->app->bind(SnappPayService::class, function ($app) {
            return $app->make('snapppay')->connection();
        });

        $this->app->bind(SnappPayClientInterface::class, function ($app) {
            return $app->make(SnappPayService::class)->client();
        });

        $this->app->bind(SnappPayClient::class, function ($app) {
            return $app->make(SnappPayService::class)->client();
        });

        $this->app->bind(TokenManagerInterface::class, function ($app) {
            return $app->make(SnappPayService::class)->tokenManager();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/snapppay.php' => $this->app->configPath('snapppay.php'),
            ], 'snapppay-config');
        }
    }
}
