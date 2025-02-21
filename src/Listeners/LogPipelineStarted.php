<?php

namespace Pollora\Datamorph\Listeners;

use Pollora\Datamorph\Events\PipelineStartedEvent;
use Illuminate\Support\Facades\Log;

class LogPipelineStarted
{
    public function handle(PipelineStartedEvent $event): void
    {
        Log::channel('datamorph')->info("Pipeline {$event->pipeline} démarré", [
            'pipeline' => $event->pipeline,
            'metadata' => $event->metadata,
            'timestamp' => now(),
        ]);
    }
} 