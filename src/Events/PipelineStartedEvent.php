<?php

namespace Pollora\Datamorph\Events;

class PipelineStartedEvent
{
    public string $pipeline;
    public array $metadata;

    public function __construct(string $pipeline, array $metadata = [])
    {
        $this->pipeline = $pipeline;
        $this->metadata = $metadata;
    }
} 