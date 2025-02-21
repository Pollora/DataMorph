<?php

namespace Pollora\Datamorph\Listeners;

use Pollora\Datamorph\Events\PipelineFailedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LogPipelineFailed
{
    public function handle(PipelineFailedEvent $event): void
    {
        // Log dans le canal datamorph
        Log::channel('datamorph')->error("Pipeline {$event->pipeline} a échoué", [
            'pipeline' => $event->pipeline,
            'stage' => $event->stage,
            'error' => $event->exception->getMessage(),
            'metadata' => $event->metadata,
            'timestamp' => now(),
        ]);

        // Enregistrement de l'erreur
        DB::table('datamorph_errors')->insert([
            'pipeline' => $event->pipeline,
            'error_message' => $event->exception->getMessage(),
            'failed_data' => $event->failedData ? json_encode($event->failedData) : null,
            'stage' => $event->stage,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Mise à jour du log principal
        DB::table('datamorph_logs')->insert([
            'pipeline' => $event->pipeline,
            'processed_rows' => 0,
            'execution_time' => 0,
            'status' => 'failed',
            'metadata' => json_encode($event->metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
} 