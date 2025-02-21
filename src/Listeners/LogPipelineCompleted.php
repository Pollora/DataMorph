<?php

namespace Pollora\Datamorph\Listeners;

use Pollora\Datamorph\Events\PipelineCompletedEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class LogPipelineCompleted
{
    public function handle(PipelineCompletedEvent $event): void
    {
        // Log dans le canal datamorph
        Log::channel('datamorph')->info("Pipeline {$event->pipeline} terminé avec succès", [
            'pipeline' => $event->pipeline,
            'processed_rows' => $event->processedRows,
            'execution_time' => $event->executionTime,
            'metadata' => $event->metadata,
            'timestamp' => now(),
        ]);

        // Enregistrement dans la base de données
        DB::table('datamorph_logs')->insert([
            'pipeline' => $event->pipeline,
            'processed_rows' => $event->processedRows,
            'execution_time' => $event->executionTime,
            'status' => 'completed',
            'metadata' => json_encode($event->metadata),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
} 