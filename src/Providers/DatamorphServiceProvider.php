<?php

namespace Pollora\Datamorph\Providers;

use Illuminate\Support\ServiceProvider;
use Pollora\Datamorph\Console\DatamorphMakeCommand;
use Pollora\Datamorph\Console\DatamorphRunCommand;

class DatamorphServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish the configuration
        $this->publishes([
            __DIR__.'/../../config/datamorph.php' => config_path('datamorph.php'),
        ], 'datamorph-config');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                DatamorphRunCommand::class,
                DatamorphMakeCommand::class,
            ]);
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/datamorph.php', 'datamorph'
        );
    }
}
