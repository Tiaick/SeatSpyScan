<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Checks;

use Seat\SpyDetection\Contracts\SpyCheck;
use Seat\SpyDetection\DTO\SpyCheckResult;
use Seat\SpyDetection\DTO\SpyContext;

class AltNetworkAnomaliesCheck implements SpyCheck
{
    public function key(): string
    {
        return 'alt_network_anomalies';
    }

    public function description(): string
    {
        return 'Young alts or missing history signals.';
    }

    public function evaluate(SpyContext $ctx): ?SpyCheckResult
    {
        if (empty($ctx->characters)) {
            return null;
        }

        $evidence = [];
        $young30 = 0;
        $young90 = 0;
        $unknown = 0;
        $now = time();

        foreach ($ctx->characters as $char) {
            $birthday = $char['birthday'] ?? null;
            $ageDays = null;
            if ($birthday) {
                $ageSeconds = $now - (int) strtotime((string) $birthday);
                $ageDays = (int) floor($ageSeconds / 86400);
            }

            $signal = 'unknown';
            if ($ageDays !== null) {
                if ($ageDays < 30) {
                    $young30++;
                    $signal = 'young_under_30d';
                } elseif ($ageDays < 90) {
                    $young90++;
                    $signal = 'young_under_90d';
                } else {
                    $signal = 'normal';
                }
            } else {
                $unknown++;
            }

            if ($signal !== 'normal') {
                $evidence[] = [
                    'character_id' => $char['character_id'] ?? null,
                    'character_name' => $char['character_name'] ?? null,
                    'age_days' => $ageDays,
                    'signal' => $signal,
                ];
            }
        }

        if (empty($evidence)) {
            return null;
        }

        $scoreDelta = min(30, ($young30 * 6) + ($young90 * 3) + ($unknown * 2));
        $severity = $scoreDelta >= 20 ? 'high' : ($scoreDelta >= 10 ? 'medium' : 'low');
        $detail = sprintf(
            'Young alts: %d under 30d, %d under 90d. Unknown ages: %d.',
            $young30,
            $young90,
            $unknown
        );

        return new SpyCheckResult(
            $this->key(),
            $severity,
            $scoreDelta,
            'Alt network anomalies',
            $detail,
            array_slice($evidence, 0, 20)
        );
    }
}
