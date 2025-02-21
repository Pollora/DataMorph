<?php

declare(strict_types=1);

namespace Pollora\Datamorph;

use Closure;
use Pollora\Datamorph\Contracts\HookInterface;

class DynamicHook implements HookInterface
{
    /**
     * Create a new dynamic hook instance.
     *
     * @param Closure $callback The closure to execute
     */
    public function __construct(
        private readonly Closure $callback
    ) {
    }

    /**
     * Execute the hook with the given dataframe.
     *
     * @param mixed $dataframe The dataframe to process
     * @param Closure|null $next The next hook to execute
     * @return \Flow\ETL\DataFrame|null
     */
    public function handle(mixed $dataframe, ?Closure $next = null): ?\Flow\ETL\DataFrame
    {
        try {
            // Execute the callback
            $result = ($this->callback)($dataframe);

            // Use the result if provided, otherwise use the original dataframe
            $modifiedDataframe = $result ?? $dataframe;
        } catch (\Throwable $e) {
            report($e);
            $modifiedDataframe = $dataframe;
        }

        return $next ? $next($modifiedDataframe) : $modifiedDataframe;
    }
}
