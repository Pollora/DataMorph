<?php

namespace Pollora\Datamorph\Providers;

use Illuminate\Support\ServiceProvider;
use Pollora\Datamorph\Pipeline\ETLPipeline;
use Pollora\Datamorph\Events\{
    PipelineStartedEvent,
    PipelineCompletedEvent,
    PipelineFailedEvent
};
use Pollora\Datamorph\Listeners\{
    LogPipelineStarted,
    LogPipelineCompleted,
    LogPipelineFailed
};
use Illuminate\Support\Facades\Event;

class DatamorphServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/datamorph.php', 'datamorph'
        );

        $this->app->singleton('datamorph', function ($app) {
            return new ETLPipeline();
        });
    }
    
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publication de la configuration
        $this->publishes([
            __DIR__.'/../../config/datamorph.php' => config_path('datamorph.php'),
        ], 'datamorph-config');

        // Publication des migrations
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'datamorph-migrations');

        // Chargement automatique des migrations
        $this->loadMigrationsFrom(__DIR__.'/../../database/migrations');

        // Enregistrement des événements
        Event::listen(PipelineStartedEvent::class, LogPipelineStarted::class);
        Event::listen(PipelineCompletedEvent::class, LogPipelineCompleted::class);
        Event::listen(PipelineFailedEvent::class, LogPipelineFailed::class);

        // Configuration du canal de log
        $this->app->make('config')->set('logging.channels.datamorph', [
            'driver' => 'daily',
            'path' => storage_path('logs/datamorph.log'),
            'level' => 'debug',
            'days' => 14,
        ]);
    }
}