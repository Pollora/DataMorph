<?php

declare(strict_types=1);

namespace Pollora\Datamorph\Console;

use Illuminate\Console\Command;

class DatamorphMakeCommand extends Command
{
    protected $signature = 'datamorph:make {pipeline}';

    protected $description = 'Generate the files for an ETL pipeline';

    /**
     * Generate the files for an ETL pipeline.
     */
    public function handle()
    {
        $pipelineName = ucfirst($this->argument('pipeline'));

        $basePath = config('datamorph.paths.etl').DIRECTORY_SEPARATOR.$pipelineName;

        if (! file_exists($basePath)) {
            if (! mkdir($basePath, 0755, true) && ! is_dir($basePath)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $basePath));
            }
        }

        $stubPath = __DIR__.'/stubs';
        $files = ['Extractor', 'Transformer', 'Loader'];

        foreach ($files as $file) {
            $filePath = "$basePath/{$pipelineName}$file.php";
            if (! file_exists($filePath)) {
                $stub = file_get_contents("$stubPath/$file.stub");
                $stub = str_replace('{{PipelineName}}', $pipelineName, $stub);
                file_put_contents($filePath, $stub);
                $this->info("File created: $filePath");
            } else {
                $this->warn("The file $filePath already exists.");
            }
        }
    }
}
