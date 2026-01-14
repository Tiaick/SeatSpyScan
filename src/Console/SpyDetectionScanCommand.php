<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Console;

use Illuminate\Console\Command;
use Seat\SpyDetection\Engine\SpyCheckRunner;
use Seat\SpyDetection\Support\SpyContextBuilder;
use Throwable;

class SpyDetectionScanCommand extends Command
{
    protected $signature = 'spy-detection:scan {character_name}';
    protected $description = 'Run a spy detection scan for a character name.';

    public function handle(SpyContextBuilder $builder, SpyCheckRunner $runner): int
    {
        $characterName = (string) $this->argument('character_name');

        try {
            $ctx = $builder->buildFromCharacterName($characterName, 0);
            $result = $runner->run($ctx);
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->line(json_encode($result->toArray(), JSON_PRETTY_PRINT));
        return self::SUCCESS;
    }
}
