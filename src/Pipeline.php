<?php

declare(strict_types=1);

namespace Pollora\Datamorph;

use Closure;
use Flow\ETL\Config;
use Flow\ETL\DataFrame;
use Flow\ETL\Flow;
use Flow\ETL\FlowContext;
use Flow\ETL\Loader;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use InvalidArgumentException;
use Pollora\Datamorph\Contracts\Extractor;
use Pollora\Datamorph\Contracts\HookInterface;
use Pollora\Datamorph\Contracts\Transformer;

use function Flow\ETL\DSL\data_frame;

class Pipeline
{
    /**
     * Default hook stages for the pipeline.
     */
    private const HOOK_STAGES = [
        'before_extract',
        'after_extract',
        'before_transform',
        'after_transform',
        'before_load',
        'after_load',
        'before_run',
        'after_run',
    ];

    /**
     * The pipeline configuration.
     */
    private array $pipelineConfig;

    /**
     * The hook manager instance.
     */
    private HookManager $hookManager;

    /**
     * Runtime dynamic hooks.
     */
    private array $dynamicHooks = [];

    /**
     * Collection of dynamic hook instances.
     */
    private array $dynamicHookInstances = [];

    /**
     * Current operation being executed.
     */
    private string $currentOperation = '';

    /**
     * Counter for generating unique IDs for dynamic hooks.
     */
    private int $dynamicHookCounter = 0;

    private FlowContext $context;

    /**
     * Create a new pipeline instance.
     */
    public function __construct(
        protected string $pipeline,
        protected Extractor $extractor,
        protected Transformer $transformer,
        protected Loader $loader,
        ?HookManager $hookManager = null
    ) {
        $this->pipelineConfig = config("datamorph.pipelines.{$this->pipeline}") ?? [];
        $config = Config::default();

        $config->pipelineConfig = $this->pipelineConfig;

        $this->context = new FlowContext($config);
        $this->context->pipeline = $this;

        if (empty($this->pipelineConfig)) {
            throw new InvalidArgumentException("Invalid pipeline configuration for '{$this->pipeline}'");
        }

        $this->hookManager = $hookManager ?? new HookManager(Container::getInstance());

        // Initialize dynamic hooks array with empty arrays for each stage
        foreach (self::HOOK_STAGES as $stage) {
            $this->dynamicHooks[$stage] = [];
        }
    }

    /**
     * Run the ETL pipeline.
     *
     * @throws BindingResolutionException
     */
    public function run(): void
    {
        $dataframe = data_frame();

        // Process extraction
        $this->currentOperation = 'extract';
        $dataframe = $this->processExtraction($this->context, $dataframe);

        // Process transformation
        $this->currentOperation = 'transform';
        $dataframe = $this->processTransformation($this->context, $dataframe);

        // Process loading
        $this->currentOperation = 'load';
        $dataframe = $this->processLoading($this->context, $dataframe);

        // Process ETL run
        $this->currentOperation = 'run';
        $this->processRun($this->context, $dataframe);

        $this->currentOperation = '';
    }

    /**
     * Process the extraction phase.
     *
     * @throws BindingResolutionException
     */
    private function processExtraction(FlowContext $context, Flow $dataframe): DataFrame
    {
        // 1. Run global hooks before extraction
        $dataframe = $this->runHooks('before_extract', $dataframe, ['context' => $context]);

        // 2. Run extractor's own before method
        $dataframe = $this->extractor->before($dataframe, $context);

        // 3. Extract data
        $dataframe = $dataframe->from($this->extractor);

        // 4. Run extractor's own after method
        $dataframe = $this->extractor->after($dataframe, $context);

        // 5. Run global hooks after extraction
        $dataframe = $this->runHooks('after_extract', $dataframe, ['context' => $context]);

        return $dataframe;
    }

    /**
     * Process the transformation phase.
     *
     * @throws BindingResolutionException
     */
    private function processTransformation(FlowContext $context, DataFrame $dataframe): DataFrame
    {
        // 1. Run global hooks before transformation
        $dataframe = $this->runHooks('before_transform', $dataframe, ['context' => $context]);

        // 2. Run transformer's own before method
        $dataframe = $this->transformer->before($dataframe, $context);

        // 3. Transform data
        $dataframe = $dataframe->transform($this->transformer);

        // 4. Run transformer's own after method
        $dataframe = $this->transformer->after($dataframe, $context);

        // 5. Run global hooks after transformation
        return $this->runHooks('after_transform', $dataframe, ['context' => $context]);
    }

    /**
     * Process the loading phase.
     *
     * @throws BindingResolutionException
     */
    private function processLoading(FlowContext $context, DataFrame $dataframe): DataFrame
    {
        // 1. Run global hooks before loading
        $dataframe = $this->runHooks('before_load', $dataframe, ['context' => $context]);

        // 2. Run loader's own before method
        $dataframe = $this->loader->before($dataframe, $context);

        // 3. Load data
        $dataframe = $dataframe->load($this->loader->fetch($context));

        // 4. Run loader's own after method
        $dataframe = $this->loader->after($dataframe, $context);

        // 5. Run global hooks after loading
        return $this->runHooks('after_load', $dataframe, ['context' => $context]);
    }

    /**
     * Process the ETL run phase.
     *
     * @throws BindingResolutionException
     */
    private function processRun(FlowContext $context, DataFrame $dataframe): void
    {
        // Run before_run hooks
        $dataframe = $this->runHooks('before_run', $dataframe, ['context' => $context]);

        // Run ETL
        $dataframe = $dataframe->run();

        // Run after_run hooks
        $this->runHooks('after_run', $dataframe, ['context' => $context]);
    }

    /**
     * Run hooks for a specific stage.
     *
     * @param  mixed  $dataframe  Flow or DataFrame depending on the stage
     *
     * @throws BindingResolutionException
     */
    private function runHooks(string $stage, mixed $dataframe, array $parameters = []): mixed
    {
        // 1. Get configured hooks
        $configuredHooks = $this->resolveHooksForStage($stage);

        // 2. Get dynamic hooks for this stage
        $dynamicHooks = $this->dynamicHooks[$stage] ?? [];

        // 3. Combine hooks (configured hooks first, then dynamic hooks)
        $allHooks = array_merge($configuredHooks, $dynamicHooks);

        // 4. Run all hooks
        if (empty($allHooks)) {
            return $dataframe;
        }

        return $this->hookManager->pipe($allHooks, $dataframe, array_merge($parameters, [
            'dynamicHookInstances' => $this->dynamicHookInstances,
            'stage' => $stage,
        ]));
    }

    /**
     * Resolve hooks for a specific stage.
     */
    private function resolveHooksForStage(string $stage): array
    {
        $hooks = $this->pipelineConfig['hooks'][$stage] ?? [];

        return ! empty($hooks) ? (is_array($hooks) ? $hooks : [$hooks]) : [];
    }

    /**
     * Register a dynamic hook to run after the current operation or a specified operation.
     *
     * @param  Closure|string|HookInterface  $hook  The hook to execute
     * @param  string|null  $operation  Optional operation name (extract, transform, load, run)
     * @return $this
     */
    public function after(Closure|string|HookInterface $hook, ?string $operation = null): self
    {
        $targetOperation = $operation ?? $this->detectCurrentOperation();

        if (empty($targetOperation)) {
            throw new InvalidArgumentException('No operation specified and unable to detect current operation');
        }

        $stage = "after_{$targetOperation}";

        if (! in_array($stage, self::HOOK_STAGES)) {
            throw new InvalidArgumentException("Invalid operation '{$targetOperation}'");
        }

        $this->addDynamicHook($stage, $hook);

        return $this;
    }

    /**
     * Detect the current operation based on the call stack.
     *
     * @return string The detected operation or the current operation if detection fails
     */
    private function detectCurrentOperation(): string
    {
        // Get the debug backtrace to analyze the call stack
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);

        foreach ($trace as $frame) {
            if (isset($frame['class'])) {
                $className = $frame['class'];

                // Check for extractor
                if (str_ends_with($className, 'Extractor') || $className === Extractor::class || is_subclass_of($className, Extractor::class)) {
                    return 'extract';
                }

                // Check for transformer
                if (str_ends_with($className, 'Transformer') || $className === Transformer::class || is_subclass_of($className, Transformer::class)) {
                    return 'transform';
                }

                // Check for loader
                if (str_ends_with($className, 'Loader') || $className === Loader::class || is_subclass_of($className, Loader::class)) {
                    return 'load';
                }
            }
        }

        // If detection fails, return the current operation
        return $this->currentOperation;
    }

    /**
     * Add a dynamic hook to the specified stage.
     *
     * @param  string  $stage  Hook stage
     * @param  Closure|string|HookInterface  $hook  The hook to execute
     */
    private function addDynamicHook(string $stage, Closure|string|HookInterface $hook): void
    {
        if ($hook instanceof Closure) {
            // Create a dynamic hook from the closure
            $dynamicHook = new DynamicHook($hook);

            // Generate a unique ID
            $hookId = $this->generateHookId();

            // Store the instance
            $this->dynamicHookInstances[$hookId] = $dynamicHook;

            // Add the hook ID to the list for this stage
            $this->dynamicHooks[$stage][] = $hookId;
        } elseif ($hook instanceof HookInterface) {
            // For hook instances, store them directly
            $hookId = $this->generateHookId();
            $this->dynamicHookInstances[$hookId] = $hook;
            $this->dynamicHooks[$stage][] = $hookId;
        } else {
            // For class names, add them directly
            $this->dynamicHooks[$stage][] = $hook;
        }
    }

    /**
     * Generate a unique ID for a dynamic hook.
     *
     * @return string Unique hook ID
     */
    private function generateHookId(): string
    {
        return 'dyn_hook_'.(++$this->dynamicHookCounter).'_'.uniqid();
    }

    /**
     * Get the current operation.
     */
    public function getCurrentOperation(): string
    {
        return $this->currentOperation;
    }

    /**
     * Get the dynamic hook instance by ID.
     *
     * @param  string  $hookId  The hook ID
     * @return HookInterface|null The hook instance or null if not found
     */
    public function getDynamicHookInstance(string $hookId): ?HookInterface
    {
        return $this->dynamicHookInstances[$hookId] ?? null;

    }
}
