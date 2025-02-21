<?php

declare(strict_types=1);

namespace Pollora\Datamorph\Console;

use Flow\ETL\Config;
use Flow\ETL\FlowContext;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Pollora\Datamorph\Pipeline;

class DatamorphRunCommand extends Command
{
    protected $signature = 'datamorph:run {pipeline}';

    protected $description = 'Execute an ETL pipeline defined in the configuration';

    /**
     * Execute an ETL pipeline defined in the configuration.
     */
    public function handle()
    {
        $pipelineName = $this->argument('pipeline');
        $config = config("datamorph.pipelines.$pipelineName");

        if (! $config) {
            $this->error("The pipeline '$pipelineName' does not exist in the configuration.");

            return 1;
        }

        $pipelineClassName = Str::studly($pipelineName);

        $extractorClass = "App\\ETL\\$pipelineClassName\\{$pipelineClassName}Extractor";
        $transformerClass = "App\\ETL\\$pipelineClassName\\{$pipelineClassName}Transformer";
        $loaderClass = "App\\ETL\\$pipelineClassName\\{$pipelineClassName}Loader";

        if (! class_exists($extractorClass) || ! class_exists($transformerClass) || ! class_exists($loaderClass)) {
            $this->error("The classes for the pipeline '$pipelineName' are not found.");

            return 1;
        }

        $pipeline = new Pipeline($pipelineName, new $extractorClass, new $transformerClass, new $loaderClass);

        $pipeline->run();
        $this->info("Pipeline '$pipelineName' executed successfully.");

        return 0;
    }
}
