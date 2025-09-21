<?php

namespace App\Providers;

use App\Container\Container;
use App\Services\ClickService;
use App\Services\WebhookService;
use App\Services\FinanceService;
use Illuminate\Support\ServiceProvider;

class ContainerServiceProvider extends ServiceProvider
{
    /**
     * Custom DI container instance
     *
     * @var Container
     */
    protected $container;

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Create custom container instance
        $this->container = new Container();

        // Register services in custom container
        $this->registerServices();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Make custom container available globally if needed
        $this->app->instance('custom.container', $this->container);
    }

    /**
     * Register services in custom container
     *
     * @return void
     */
    protected function registerServices()
    {
        // Register ClickService as singleton
        $this->container->singleton(ClickService::class, function () {
            return new ClickService();
        });

        // Register WebhookService as singleton
        $this->container->singleton(WebhookService::class, function () {
            return new WebhookService();
        });

        // Register FinanceService as singleton
        $this->container->singleton(FinanceService::class, function () {
            return new FinanceService();
        });
    }
}
