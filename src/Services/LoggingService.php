<?php

namespace Pollora\Datamorph\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LoggingService
{
    protected string $pipelineName;
    protected float $startTime;
    protected array $metadata = [];

    public function __construct(string $pipelineName)
    {
        $this->pipelineName = $pipelineName;
        $this->startTime = microtime(true);
    }

    public function addMetadata(array $metadata): self
    {
        $this->metadata = array_merge($this->metadata, $metadata);
        return $this;
    }

    public function logSuccess(int $processedRows): void
    {
        $executionTime = microtime(true) - $this->startTime;
        
        DB::table('datamorph_logs')->insert([
            'pipeline' => $this->pipelineName,
            'processed_rows' => $processedRows,
            'execution_time' => $executionTime,
            'status' => 'success',
            'metadata' => json_encode($this->metadata),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Log::channel('datamorph')->info("Pipeline {$this->pipelineName} completed successfully", [
            'processed_rows' => $processedRows,
            'execution_time' => $executionTime,
            'metadata' => $this->metadata,
        ]);
    }

    public function logError(string $stage, string $errorMessage, ?array $failedData = null, ?int $batchNumber = null): void
    {
        $executionTime = microtime(true) - $this->startTime;

        DB::table('datamorph_errors')->insert([
            'pipeline' => $this->pipelineName,
            'error_message' => $errorMessage,
            'failed_data' => $failedData ? json_encode($failedData) : null,
            'stage' => $stage,
            'batch_number' => $batchNumber,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        DB::table('datamorph_logs')->insert([
            'pipeline' => $this->pipelineName,
            'processed_rows' => 0,
            'execution_time' => $executionTime,
            'status' => 'error',
            'metadata' => json_encode($this->metadata),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        Log::channel('datamorph')->error("Pipeline {$this->pipelineName} failed at {$stage} stage", [
            'error' => $errorMessage,
            'batch_number' => $batchNumber,
            'metadata' => $this->metadata,
        ]);
    }
} 