<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Matakltm\LaravelModelGraph\Services\GraphBuilderService;

/**
 * Class GenerateGraphCommand
 *
 * Artisan command to generate the model graph JSON file.
 */
class GenerateGraphCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'model-graph:generate
                            {--force : Overwrite existing file}
                            {--dry-run : Only simulate the generation}
                            {--pretty : Pretty print the JSON output}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the Eloquent model graph JSON';

    /**
     * Execute the console command.
     */
    public function handle(GraphBuilderService $builder): int
    {
        $this->info('Generating model graph...');

        /** @var string $storagePath */
        $storagePath = Config::get('model-graph.storage_path');

        if (! $this->option('dry-run') && ! $this->option('force') && File::exists($storagePath) && ! $this->confirm(sprintf('File [%s] already exists. Overwrite?', $storagePath))) {
            $this->warn('Generation cancelled.');

            return self::SUCCESS;
        }

        $models = $builder->getModels();
        $totalModels = count($models);

        if ($totalModels === 0) {
            $this->warn('No models found to process.');
        }

        $bar = $this->output->createProgressBar($totalModels);
        $bar->start();

        $data = $builder->generate($models, function () use ($bar): void {
            $bar->advance();
        });

        $bar->finish();
        $this->newLine();

        /** @var int $jsonOptions */
        $jsonOptions = Config::get('model-graph.json_options', JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($this->option('pretty')) {
            $jsonOptions |= JSON_PRETTY_PRINT;
        }

        $json = json_encode($data, $jsonOptions);

        if ($this->option('dry-run')) {
            $this->info('Dry run: Graph data generated but not saved.');
            $this->line((string) $json);

            return self::SUCCESS;
        }

        $directory = dirname($storagePath);
        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        File::put($storagePath, (string) $json);

        $this->info('Model graph successfully generated and saved to: ' . $storagePath);

        return self::SUCCESS;
    }
}
