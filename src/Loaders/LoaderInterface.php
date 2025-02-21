<?php

namespace Pollora\Datamorph\Loaders;

interface LoaderInterface
{
    /**
     * Charge les données dans la destination spécifiée.
     *
     * @param iterable $data
     * @return void
     */
    public function load(iterable $data): void;

    /**
     * Configure le chargeur avec les options spécifiées.
     *
     * @param array $options
     * @return self
     */
    public function configure(array $options): self;
} 