<?php

declare(strict_types=1);

namespace Pollora\Datamorph\Contracts;

use Flow\ETL\DataFrame;
use Flow\ETL\Extractor as ExtractorContract;
use Flow\ETL\FlowContext;
use Generator;

use function Flow\ETL\DSL\array_to_rows;

abstract class Extractor implements ExtractorContract
{
    abstract protected function handle(FlowContext $context): array;

    public function extract(FlowContext $context): Generator
    {
        yield array_to_rows($this->handle($context));
    }

    /**
     * Method called before extraction.
     * This default implementation does nothing and returns the unchanged dataframe.
     *
     * @param  DataFrame|mixed  $dataframe
     * @return DataFrame|mixed
     */
    public function before(mixed $dataframe, FlowContext $context): mixed
    {
        // Par défaut, ne fait rien
        return $dataframe;
    }

    /**
     * Method called after extraction.
     * This default implementation does nothing and returns the unchanged dataframe.
     *
     * @param  DataFrame|mixed  $dataframe
     * @return DataFrame|mixed
     */
    public function after(mixed $dataframe, FlowContext $context): mixed
    {
        // Par défaut, ne fait rien
        return $dataframe;
    }
}
