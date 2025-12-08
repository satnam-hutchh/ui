<?php

namespace Hutchh\Ui;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Hutchh\Ui\Console;

class UiServiceProvider extends ServiceProvider
{
    /**
     * Register the package services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\ControllerMakeCommand::class,
                Console\FilterMakeCommand::class,
                Console\TypesMakeCommand::class,
                Console\RepositoryMakeCommand::class
            ]);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Route::mixin(new AuthRouteMethods);
    }
}
