<?php

namespace Pollora\Datamorph\Events;

class PipelineCompletedEvent
{
    public string $pipeline;
    public int $processedRows;
    public float $executionTime;
    public array $metadata;

    public function __construct(string $pipeline, int $processedRows, float $executionTime, array $metadata = [])
    {
        $this->pipeline = $pipeline;
        $this->processedRows = $processedRows;
        $this->executionTime = $executionTime;
        $this->metadata = $metadata;
    }
} 