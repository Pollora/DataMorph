<?php

declare(strict_types=1);

namespace Pollora\Datamorph;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Pollora\Datamorph\Contracts\HookInterface;

class HookManager
{
    /**
     * The IoC container instance.
     */
    protected Container $container;

    /**
     * Create a new hook manager instance.
     */
    public function __construct(?Container $container = null)
    {
        $this->container = $container ?? Container::getInstance();
    }

    /**
     * Execute a series of hooks on the given dataframe.
     *
     * @param  array|string  $hooks  Array of hook class names or pipeline.hook format strings
     * @param  mixed  $dataframe  The dataframe to process
     * @param  array  $parameters  Additional parameters to pass to the hooks
     *
     * @throws BindingResolutionException
     */
    public function pipe(array|string $hooks, mixed $dataframe, array $parameters = []): mixed
    {
        // Flatten the hooks array (handles nested arrays from config)
        $hooks = $this->flattenHooks($hooks);

        if (empty($hooks)) {
            return $dataframe;
        }

        return $this->runHooks(Collection::make($hooks), $dataframe, $parameters);
    }

    /**
     * Flatten a potentially nested array of hooks.
     *
     * @param  array|string  $hooks  Hooks to flatten
     */
    private function flattenHooks(array|string $hooks): array
    {
        if (is_string($hooks)) {
            $hooks = $this->resolveHooksFromString($hooks);
        }

        $result = [];

        foreach ($hooks as $hook) {
            if (is_array($hook)) {
                $result = array_merge($result, $this->flattenHooks($hook));
            } elseif (! empty($hook)) {
                $result[] = $hook;
            }
        }

        return $result;
    }

    /**
     * Resolve hook classes from a string format like 'pipeline.hookName'.
     *
     * @param  string  $hookString  Hook string in 'pipeline.hookName' format
     */
    protected function resolveHooksFromString(string $hookString): array
    {
        // Support for Laravel-style 'pipeline.hookName' format
        if (str_contains($hookString, '.')) {
            [$pipeline, $hookName] = explode('.', $hookString, 2);
            $hookClasses = config("datamorph.pipelines.{$pipeline}.hooks.{$hookName}", []);

            return is_array($hookClasses) ? $hookClasses : [$hookClasses];
        }

        return [$hookString];
    }

    /**
     * Run the hooks on the dataframe.
     *
     * @param  Collection  $hooks  Collection of hook class names
     * @param  mixed  $dataframe  The dataframe to process
     * @param  array  $parameters  Additional parameters to pass to the hooks
     *
     * @throws BindingResolutionException
     */
    protected function runHooks(Collection $hooks, mixed $dataframe, array $parameters = []): mixed
    {
        $stage = $parameters['stage'] ?? 'unknown';

        if ($hooks->isEmpty()) {
            return $dataframe;
        }

        $firstHook = $hooks->shift();

        return $this->runHook($firstHook, $dataframe, function (mixed $passable) use ($hooks, $parameters) {
            return $this->runHooks($hooks, $passable, $parameters);
        }, $parameters);
    }

    /**
     * Run a single hook.
     *
     * @param  mixed  $hook  The hook class name, instance, or ID
     * @param  mixed  $dataframe  The dataframe to process
     * @param  Closure  $next  The next hook to execute
     * @param  array  $parameters  Additional parameters to pass to the hook
     *
     * @throws BindingResolutionException
     */
    protected function runHook(mixed $hook, mixed $dataframe, Closure $next, array $parameters = []): mixed
    {
        $stage = $parameters['stage'] ?? 'unknown';

        // Check if the hook is null or empty, skip in that case
        if (empty($hook)) {
            return $next($dataframe);
        }

        // If hook is already an instance, use it directly
        if ($hook instanceof HookInterface) {
            return $hook->handle($dataframe, $next);
        }

        // Check if this is a dynamic hook ID - starts with 'dyn_hook_'
        if (is_string($hook) && str_starts_with($hook, 'dyn_hook_')) {
            $dynamicHookInstances = $parameters['dynamicHookInstances'] ?? [];

            if (isset($dynamicHookInstances[$hook])) {
                $hookInstance = $dynamicHookInstances[$hook];

                return $hookInstance->handle($dataframe, $next);
            }

            // If the hook ID was not found, log an error and continue
            report(new \RuntimeException("Dynamic hook with ID '{$hook}' not found"));

            return $next($dataframe);
        }

        // Otherwise, treat as class name string
        $hookClass = (string) $hook;

        if (! class_exists($hookClass)) {
            report(new InvalidArgumentException("Hook class '{$hookClass}' does not exist"));

            return $next($dataframe);
        }

        // Check if instance is already registered in container
        try {
            if ($this->container->bound($hookClass)) {
                $hookInstance = $this->container->make($hookClass);
            } else {
                $hookInstance = $this->container->make($hookClass, $parameters);
            }
        } catch (BindingResolutionException $e) {
            // Log the error but don't stop the pipeline
            report($e);

            return $next($dataframe);
        }

        if (! ($hookInstance instanceof HookInterface)) {
            report(new InvalidArgumentException("Hook class '{$hookClass}' must implement HookInterface"));

            return $next($dataframe);
        }

        return $hookInstance->handle($dataframe, $next);
    }
}
