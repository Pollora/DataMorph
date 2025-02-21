<?php

declare(strict_types=1);

namespace Pollora\Datamorph\Contracts;

use Flow\ETL\DataFrame;
use Flow\ETL\FlowContext;
use Flow\ETL\Row\Entries;
use Flow\ETL\Rows;
use Flow\ETL\Transformer as TransformerContract;

use function Flow\ETL\DSL\array_to_rows;

abstract class Transformer implements TransformerContract
{
    abstract protected function handle(Entries $entries, FlowContext $context): array;

    public function transform(Rows $rows, FlowContext $context): Rows
    {
        $handle = $this->handle($rows->entries()[0], $context);

        return array_to_rows($handle);
    }

    public function __serialize(): array
    {
        // TODO: Implement __serialize() method.
    }

    public function __unserialize(array $data): void
    {
        // TODO: Implement __unserialize() method.
    }

    /**
     * Method called before transformation.
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
     * Method called after transformation.
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
