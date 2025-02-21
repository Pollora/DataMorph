<?php

declare(strict_types=1);

namespace Pollora\Datamorph\Contracts;

use Flow\ETL\DataFrame;
use Flow\ETL\FlowContext;
use Flow\ETL\Loader as LoaderContract;
use Flow\ETL\Rows;

abstract class Loader implements LoaderContract
{
    abstract protected function handle(FlowContext $context): LoaderContract;

    public function fetch(FlowContext $context): object
    {
        return $this->handle($context);
    }

    public function load(Rows $rows, FlowContext $context): void {}

    public function __serialize(): array
    {
        // TODO: Implement __serialize() method.
    }

    public function __unserialize(array $data): void
    {
        // TODO: Implement __unserialize() method.
    }

    /**
     * Method called before loading.
     * This default implementation does nothing and returns the unchanged dataframe.
     *
     * @param DataFrame|mixed $dataframe
     * @param FlowContext $context
     * @return DataFrame|mixed
     */
    public function before(mixed $dataframe, FlowContext $context): mixed
    {
        // Par défaut, ne fait rien
        return $dataframe;
    }

    /**
     * Method called after loading.
     * This default implementation does nothing and returns the unchanged dataframe.
     *
     * @param DataFrame|mixed $dataframe
     * @param FlowContext $context
     * @return DataFrame|mixed
     */
    public function after(mixed $dataframe, FlowContext $context): mixed
    {
        // Par défaut, ne fait rien
        return $dataframe;
    }
}
