<?php

declare(strict_types=1);

namespace Matakltm\LaravelModelGraph\Commands;

use Illuminate\Console\Command;

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
    protected $signature = 'model-graph:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the Eloquent model graph JSON';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating model graph...');

        return 0;
    }
}
