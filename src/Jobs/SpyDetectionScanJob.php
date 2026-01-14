<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Seat\SpyDetection\Engine\SpyCheckRunner;
use Seat\SpyDetection\Support\SpyContextBuilder;
use Throwable;

class SpyDetectionScanJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $token,
        private readonly string $characterName,
        private readonly int $requestedByUserId
    ) {
    }

    public function handle(SpyContextBuilder $builder, SpyCheckRunner $runner): void
    {
        $cacheKey = $this->cacheKey($this->token);
        $ttl = (int) config('spy-detection.scan.cache_ttl_minutes', 30);

        try {
            $ctx = $builder->buildFromCharacterName($this->characterName, $this->requestedByUserId);
            $result = $runner->run($ctx);
            Cache::put($cacheKey, [
                'status' => 'completed',
                'result' => $result->toArray(),
            ], now()->addMinutes($ttl));
        } catch (Throwable $e) {
            Cache::put($cacheKey, [
                'status' => 'failed',
                'error' => 'Scan failed.',
            ], now()->addMinutes($ttl));
        }
    }

    private function cacheKey(string $token): string
    {
        return 'spy_scan:' . $token;
    }
}
