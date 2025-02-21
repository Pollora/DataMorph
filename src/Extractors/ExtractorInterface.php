<?php

namespace Pollora\Datamorph\Extractors;

interface ExtractorInterface
{
    /**
     * Extrait les données de la source.
     *
     * @return iterable
     */
    public function extract(): iterable;

    /**
     * Configure l'extracteur avec les options spécifiées.
     *
     * @param array $options
     * @return self
     */
    public function configure(array $options): self;
} 