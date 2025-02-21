# Datamorph

Datamorph is a Laravel package that allows you to create and run Flow PHP ETL (Extract, Transform, Load) pipelines in a structured and extensible way. This documentation will guide you through the installation, configuration, and usage of the package.

## Table of Contents

- [Installation](#installation)
- [Concepts](#concepts)
- [Configuration](#configuration)
- [Creating an ETL Pipeline](#creating-an-etl-pipeline)
- [Running an ETL Pipeline](#running-an-etl-pipeline)
- [Hooks](#hooks)
- [Concrete Examples](#concrete-examples)

## Installation

Install the package via Composer:

```bash
composer require pollora/datamorph
```

## Concepts

Datamorph is built around three main components:

1. **Extractors**: Retrieve data from various sources (databases, APIs, files, etc.)
2. **Transformers**: Transform the retrieved data according to your needs
3. **Loaders**: Load the transformed data to their final destination

These three components are orchestrated in a **Pipeline** that also manages **Hooks** that allow you to intervene at different stages of the process.

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=datamorph-config
```

This will create a `config/datamorph.php` file where you can configure your ETL pipelines:

```php
return [
    'pipelines' => [
        'stock' => [
            'hooks' => [
                'before_extract' => [
                    App\ETL\Stock\Hooks\BeforeStockExtract::class,
                ],
                'after_extract' => [
                    // Hooks to execute after extraction
                ],
                'before_transform' => [
                    // Hooks to execute before transformation
                ],
                'after_transform' => [
                    // Hooks to execute after transformation
                ],
                'before_load' => [
                    // Hooks to execute before loading
                ],
                'after_load' => [
                    // Hooks to execute after loading
                ],
                'before_run' => [
                    App\ETL\Stock\Hooks\BeforeStockRun::class,
                ],
                'after_run' => [
                    // Hooks to execute after complete execution
                ],
            ],
        ],
        // Other pipelines...
    ],
];
```

## Creating an ETL Pipeline

### Automatic File Generation

Datamorph includes an Artisan command that automatically generates the necessary files for a new pipeline:

```bash
php artisan datamorph:make product
```

This command will create the following files in the `app/ETL/Product/` directory:

- `ProductExtractor.php` - For data extraction
- `ProductTransformer.php` - For data transformation
- `ProductLoader.php` - For loading transformed data

### Structure of Generated Files

#### Extractor

```php
<?php

declare(strict_types=1);

namespace App\ETL\Product;

use Flow\ETL\FlowContext;
use Pollora\Datamorph\Contracts\Extractor;

class ProductExtractor extends Extractor
{
    public function handle(FlowContext $context): array
    {
        // Data extraction logic
        // Returns an array of raw data
        return [];
    }
}
```

#### Transformer

```php
<?php

declare(strict_types=1);

namespace App\ETL\Product;

use Flow\ETL\FlowContext;
use Pollora\Datamorph\Contracts\Transformer;

class ProductTransformer extends Transformer
{
    public function handle(array $rows, FlowContext $context): array
    {
        // Data transformation logic
        // Receives raw data and returns transformed data
        return $rows;
    }
}
```

#### Loader

```php
<?php

declare(strict_types=1);

namespace App\ETL\Product;

use Flow\ETL\Rows;
use Flow\ETL\FlowContext;
use Pollora\Datamorph\Contracts\Loader;
use Flow\ETL\Loader as FlowLoader;

class ProductLoader extends Loader
{
    public function handle(FlowContext $context): FlowLoader
    {
        // Data loading logic
        // Returns a Flow ETL loader
        return to_memory();
    }
}
```

## Running an ETL Pipeline

Once your components are implemented and your pipeline is configured, you can run it with the Artisan command:

```bash
php artisan datamorph:run stock
```

This command:
1. Checks that the pipeline exists in the configuration
2. Checks that the Extractor, Transformer, and Loader classes exist
3. Instantiates these classes and creates a Pipeline
4. Runs the Pipeline with the configured hooks

### Running in Code

You can also run a pipeline programmatically:

```php
use Pollora\Datamorph\Pipeline;
use App\ETL\Stock\StockExtractor;
use App\ETL\Stock\StockTransformer;
use App\ETL\Stock\StockLoader;

$pipeline = new Pipeline(
    'stock',
    new StockExtractor(),
    new StockTransformer(),
    new StockLoader()
);

$pipeline->run();
```

## Hooks

Hooks are a powerful mechanism in Datamorph that allows you to intervene at different stages of an ETL pipeline. There are three ways to implement hooks in Datamorph, each with its own use cases.

### Configuration-based Hooks

The first approach is to define hooks in the `config/datamorph.php` configuration file. This method is ideal for recurring hooks that need to be applied to every pipeline execution.

#### Configuration

```php
// config/datamorph.php
return [
    'pipelines' => [
        'stock' => [
            'hooks' => [
                'before_extract' => [
                    App\ETL\Stock\Hooks\BeforeStockExtract::class,
                ],
                'after_extract' => [
                    App\ETL\Stock\Hooks\AfterStockExtract::class,
                ],
                'before_transform' => [
                    App\ETL\Stock\Hooks\BeforeStockTransform::class,
                ],
                'after_transform' => [
                    App\ETL\Stock\Hooks\AfterStockTransform::class,
                ],
                'before_load' => [
                    App\ETL\Stock\Hooks\BeforeStockLoad::class,
                ],
                'after_load' => [
                    App\ETL\Stock\Hooks\AfterStockLoad::class,
                ],
                'before_run' => [
                    App\ETL\Stock\Hooks\BeforeStockRun::class,
                ],
                'after_run' => [
                    App\ETL\Stock\Hooks\AfterStockRun::class,
                ],
            ],
        ],
    ],
];
```

#### Hook Implementation

Each hook must implement the `HookInterface`:

```php
<?php

namespace App\ETL\Stock\Hooks;

use Closure;
use Flow\ETL\DataFrame;
use Flow\ETL\Filesystem\SaveMode;
use Pollora\Datamorph\Contracts\HookInterface;

class BeforeStockRun implements HookInterface
{
    /**
     * Execute the hook with the given dataframe.
     *
     * @param mixed $dataframe The dataframe to process
     * @param Closure|null $next The next hook to execute
     * @return mixed
     */
    public function handle(mixed $dataframe, ?Closure $next = null): mixed
    {
        // Apply hook logic
        if ($dataframe instanceof DataFrame) {
            $dataframe = $dataframe->mode(SaveMode::Overwrite);
        }
        
        // Pass to the next hook in the chain
        return $next ? $next($dataframe) : $dataframe;
    }
}
```

### Dynamic Hooks

The second approach uses the `$context->pipeline->after()` methods to register hooks dynamically during pipeline execution. This method is particularly useful for conditional behaviors or hooks that depend on the current state of the pipeline.

#### Usage

```php
// In an ETL component (Extractor, Transformer, Loader)
public function handle(FlowContext $context): array
{   
    // Add a hook after the current operation
    $context->pipeline->after(function ($dataframe) {
        // Hook logic
        log::info("After extraction");
        return $dataframe;
    }); // The operation is automatically detected
    
    // ...
}
```

#### Supported Hook Types

You can pass different types of hooks to the `after()` method:

1. **A Closure (anonymous function)** - Will be automatically wrapped in a `DynamicHook`
2. **An instance of a class implementing `HookInterface`** - Will be used directly
3. **A class name** - The class will be resolved via Laravel's IoC container

#### Automatic Operation Detection

If you don't explicitly specify the operation, Datamorph will detect it automatically based on the calling context:

- In an `Extractor`, the operation will be `extract`
- In a `Transformer`, the operation will be `transform`
- In a `Loader`, the operation will be `load`

### Hooks via before/after Methods

The third approach is to directly implement the `before()` and `after()` methods in your `Extractor`, `Transformer`, and `Loader` classes. This method is the simplest and most direct for standard behaviors.

#### Implementation

```php
<?php

namespace App\ETL\Stock;

use Flow\ETL\FlowContext;
use Illuminate\Support\Facades\Log;
use Pollora\Datamorph\Contracts\Extractor;

class StockExtractor extends Extractor
{
    /**
     * Extract stock data.
     */
    public function handle(FlowContext $context): array
    {
        // Extraction logic
        return $results;
    }

    /**
     * Method executed before extraction.
     */
    public function before(mixed $dataframe, FlowContext $context): mixed
    {
        Log::info("Preparing extraction");
        return $dataframe;
    }

    /**
     * Method executed after extraction.
     */
    public function after(mixed $dataframe, FlowContext $context): mixed
    {
        Log::info("Extraction completed");
        return $dataframe;
    }
}
```

The `before()` and `after()` methods are automatically called by the pipeline at the appropriate times, without any additional configuration.

### Execution Order and Combining Approaches

All three approaches can be combined in the same pipeline. The execution order is as follows:

1. Hooks configured in `config/datamorph.php`
2. The `before()` method of the relevant ETL component
3. Main operation (extraction, transformation, loading)
4. The `after()` method of the relevant ETL component
5. Dynamic hooks registered via `$pipeline->before()` and `$pipeline->after()`

This combination offers great flexibility and can address a variety of use cases.

### Usage Examples

#### Example 1: Configuration for Validation Before Extraction

```php
// config/datamorph.php
'hooks' => [
    'before_extract' => [
        App\ETL\Stock\Hooks\ValidateSourceHook::class,
    ],
]
```

```php
// App\ETL\Stock\Hooks\ValidateSourceHook.php
public function handle(mixed $dataframe, ?Closure $next = null): mixed
{
    // Check if the data source is available
    if (!$this->isSourceAvailable()) {
        throw new \RuntimeException("Data source is not available");
    }
    
    return $next($dataframe);
}
```

#### Example 2: Dynamic Hooks for Conditional Logging

```php
// In StockExtractor
public function handle(FlowContext $context): array
{
    // Add logging hooks if in debug mode
    if (config('app.debug')) {
        $context->pipeline->before(function ($dataframe) {
            Log::debug("Starting transformation");
            return $dataframe;
        }, 'transform');
        
        $context->pipeline->after(function ($dataframe) {
            Log::debug("Transformation completed");
            return $dataframe;
        }, 'transform');
    }
    
    // ...
}
```

#### Example 3: Before/After Methods for Connection Management

```php
// In DatabaseExtractor
public function before(mixed $dataframe, FlowContext $context): mixed
{
    // Open database connection
    $this->connection = DB::connection('source');
    Log::info("Database connection established");
    
    return $dataframe;
}

public function after(mixed $dataframe, FlowContext $context): mixed
{
    // Close connection after extraction
    if ($this->connection) {
        $this->connection = null;
        Log::info("Database connection closed");
    }
    
    return $dataframe;
}
```

#### Example 4: Combining Approaches for a Complete Pipeline

```php
// config/datamorph.php - Global hooks
'hooks' => [
    'before_run' => [
        App\ETL\Global\Hooks\LogStartHook::class,
    ],
    'after_run' => [
        App\ETL\Global\Hooks\LogEndHook::class,
    ],
]

// StockExtractor.php - Before/After methods
public function before(mixed $dataframe, FlowContext $context): mixed
{
    // Extraction-specific preparation
    return $dataframe;
}

// In a component's handle method - Dynamic hooks
public function handle(FlowContext $context): array
{
    // Dynamic hook for a specific case
    if ($someCondition) {
        $context->pipeline->after(function ($dataframe) {
            // Conditional logic
            return $dataframe;
        });
    }
    
    // ...
}
```

This combination of approaches provides you with a flexible and powerful hook system capable of addressing a variety of needs in your ETL pipelines.
