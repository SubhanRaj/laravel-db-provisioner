<?php

namespace Subhanraj\LaravelDbProvisioner;

use Illuminate\Support\ServiceProvider;
use Subhanraj\LaravelDbProvisioner\Commands\ProvisionDatabaseCommand;

class LaravelDbProvisionerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ProvisionDatabaseCommand::class,
            ]);
        }
    }
}
