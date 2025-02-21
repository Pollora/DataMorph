<?php

namespace Pollora\Datamorph\Events;

use Throwable;

class PipelineFailedEvent
{
    public string $pipeline;
    public string $stage;
    public Throwable $exception;
    public ?array $failedData;
    public array $metadata;

    public function __construct(
        string $pipeline,
        string $stage,
        Throwable $exception,
        ?array $failedData = null,
        array $metadata = []
    ) {
        $this->pipeline = $pipeline;
        $this->stage = $stage;
        $this->exception = $exception;
        $this->failedData = $failedData;
        $this->metadata = $metadata;
    }
} 