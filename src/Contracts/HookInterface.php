<?php

declare(strict_types=1);

namespace Pollora\Datamorph\Contracts;

use Closure;
use Flow\ETL\DataFrame;

interface HookInterface
{
    /**
     * Execute the hook with the given dataframe.
     *
     * @param  DataFrame|null  $dataframe  The dataframe to process
     * @param  Closure|null  $next  The next hook to execute
     * @return DataFrame|null
     */
    public function handle(?DataFrame $dataframe, ?Closure $next = null): ?DataFrame;
}
