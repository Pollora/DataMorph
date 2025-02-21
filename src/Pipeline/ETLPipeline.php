<?php

namespace Pollora\Datamorph\Pipeline;

use Pollora\Datamorph\Extractors\ExtractorInterface;
use Pollora\Datamorph\Transformers\TransformerInterface;
use Pollora\Datamorph\Loaders\LoaderInterface;
use Pollora\Datamorph\Services\LoggingService;
use Pollora\Datamorph\Services\BatchManager;
use Pollora\Datamorph\Events\{
    PipelineStartedEvent,
    PipelineCompletedEvent,
    PipelineFailedEvent
};
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Throwable;

class ETLPipeline
{
    protected ?ExtractorInterface $extractor = null;
    protected ?TransformerInterface $transformer = null;
    protected ?LoaderInterface $loader = null;
    protected array $options = [];
    protected LoggingService $logger;
    protected BatchManager $batchManager;
    protected bool $async = false;
    protected ?string $asyncJob = null;
    protected string $pipelineName;

    public function __construct(string $pipelineName = 'default')
    {
        $this->pipelineName = $pipelineName;
        $this->logger = new LoggingService(static::class);
        $this->batchManager = new BatchManager(
            config('datamorph.batch_size', 500)
        );
    }

    /**
     * Définit l'extracteur à utiliser.
     *
     * @param ExtractorInterface $extractor
     * @param array $options
     * @return self
     */
    public function from(ExtractorInterface $extractor, array $options = []): self
    {
        $this->extractor = $extractor;
        $this->extractor->configure($options);
        return $this;
    }

    /**
     * Définit le transformateur à utiliser.
     *
     * @param TransformerInterface $transformer
     * @param array $options
     * @return self
     */
    public function transform(TransformerInterface $transformer, array $options = []): self
    {
        $this->transformer = $transformer;
        $this->transformer->configure($options);
        return $this;
    }

    /**
     * Définit le chargeur à utiliser.
     *
     * @param LoaderInterface $loader
     * @param array $options
     * @return self
     */
    public function to(LoaderInterface $loader, array $options = []): self
    {
        $this->loader = $loader;
        $this->loader->configure($options);
        return $this;
    }

    /**
     * Configure les options globales du pipeline.
     *
     * @param array $options
     * @return self
     */
    public function withOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);
        $this->logger->addMetadata(['options' => $options]);
        return $this;
    }

    public function async(string $job): self
    {
        $this->async = true;
        $this->asyncJob = $job;
        return $this;
    }

    /**
     * Exécute le pipeline ETL.
     *
     * @return void
     * @throws \RuntimeException
     */
    public function process(): void
    {
        if (!$this->extractor || !$this->loader) {
            throw new RuntimeException('Extracteur et chargeur requis pour exécuter le pipeline.');
        }

        $startTime = microtime(true);
        $totalProcessed = 0;

        try {
            // Déclencher l'événement de démarrage
            Event::dispatch(new PipelineStartedEvent($this->pipelineName, [
                'options' => $this->options,
                'async' => $this->async
            ]));

            $data = $this->extractor->extract();

            if ($this->async && $this->asyncJob) {
                $this->batchManager->processAsync($data, $this->asyncJob);
                return;
            }

            foreach ($this->batchManager->chunk($data) as $batch) {
                try {
                    if ($this->transformer) {
                        $batch = $this->transformer->transform($batch);
                    }

                    $this->loader->load($batch);
                    $totalProcessed += $batch->count();

                } catch (Throwable $e) {
                    Event::dispatch(new PipelineFailedEvent(
                        $this->pipelineName,
                        'transform_load',
                        $e,
                        $batch->toArray(),
                        ['batch_number' => $this->batchManager->getCurrentBatch()]
                    ));
                    throw $e;
                }
            }

            $executionTime = microtime(true) - $startTime;

            // Déclencher l'événement de succès
            Event::dispatch(new PipelineCompletedEvent(
                $this->pipelineName,
                $totalProcessed,
                $executionTime,
                ['options' => $this->options]
            ));

        } catch (Throwable $e) {
            // Déclencher l'événement d'échec
            Event::dispatch(new PipelineFailedEvent(
                $this->pipelineName,
                'pipeline',
                $e,
                null,
                ['options' => $this->options]
            ));
            throw $e;
        }
    }
} 