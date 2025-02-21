<?php

namespace Pollora\Datamorph\Services;

use Generator;
use Illuminate\Support\Collection;

class BatchManager
{
    protected int $batchSize;
    protected int $currentBatch = 0;

    public function __construct(int $batchSize = 500)
    {
        $this->batchSize = $batchSize;
    }

    public function getCurrentBatch(): int
    {
        return $this->currentBatch;
    }

    /**
     * Divise un itérable en lots
     *
     * @param iterable $items
     * @return Generator
     */
    public function chunk(iterable $items): Generator
    {
        $batch = [];
        $count = 0;

        foreach ($items as $item) {
            $batch[] = $item;
            $count++;

            if ($count === $this->batchSize) {
                $this->currentBatch++;
                yield new Collection($batch);
                $batch = [];
                $count = 0;
            }
        }

        if (!empty($batch)) {
            $this->currentBatch++;
            yield new Collection($batch);
        }
    }

    /**
     * Traite les données par lots avec une fonction de callback
     *
     * @param iterable $items
     * @param callable $callback
     * @return Generator
     */
    public function process(iterable $items, callable $callback): Generator
    {
        foreach ($this->chunk($items) as $batch) {
            yield $callback($batch);
        }
    }

    /**
     * Traite les données par lots de manière asynchrone
     *
     * @param iterable $items
     * @param string $job
     * @return void
     */
    public function processAsync(iterable $items, string $job): void
    {
        foreach ($this->chunk($items) as $batch) {
            dispatch(new $job($batch, $this->currentBatch));
        }
    }
} 