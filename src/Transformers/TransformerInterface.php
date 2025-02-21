<?php

namespace Pollora\Datamorph\Transformers;

interface TransformerInterface
{
    /**
     * Transforme les données selon les règles définies.
     *
     * @param iterable $data
     * @return iterable
     */
    public function transform(iterable $data): iterable;

    /**
     * Configure le transformateur avec les options spécifiées.
     *
     * @param array $options
     * @return self
     */
    public function configure(array $options): self;
} 