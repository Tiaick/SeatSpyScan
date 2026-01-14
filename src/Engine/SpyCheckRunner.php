<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Engine;

use Seat\SpyDetection\Contracts\SpyCheck;
use Seat\SpyDetection\DTO\SpyCheckResult;
use Seat\SpyDetection\DTO\SpyContext;
use Seat\SpyDetection\DTO\SpyScanResult;
use Seat\SpyDetection\Support\RiskScoreMapper;

class SpyCheckRunner
{
    /** @var SpyCheck[] */
    private array $checks;

    /**
     * @param SpyCheck[]|null $checks
     */
    public function __construct(?array $checks = null)
    {
        $this->checks = $checks ?? $this->buildChecksFromConfig();
    }

    public function run(SpyContext $ctx): SpyScanResult
    {
        $findings = [];
        $score = 0;

        foreach ($this->checks as $check) {
            $result = $check->evaluate($ctx);
            if (!$result instanceof SpyCheckResult) {
                continue;
            }
            $findings[] = $result;
            $score += max(0, $result->scoreDelta);
        }

        $score = max(0, min(100, $score));
        $riskLevel = RiskScoreMapper::mapRiskLevel($score);

        $summary = $this->buildSummary($findings);

        return new SpyScanResult(
            $score,
            $riskLevel,
            $findings,
            $summary,
            [
                'generated_at' => gmdate('c'),
                'check_count' => count($findings),
            ]
        );
    }

    /**
     * @param SpyCheckResult[] $findings
     * @return array<int, array<string, mixed>>
     */
    private function buildSummary(array $findings): array
    {
        usort($findings, static function (SpyCheckResult $a, SpyCheckResult $b): int {
            return $b->scoreDelta <=> $a->scoreDelta;
        });

        $top = array_slice($findings, 0, 3);

        return array_map(static function (SpyCheckResult $result): array {
            return [
                'key' => $result->key,
                'title' => $result->title,
                'severity' => $result->severity,
                'score_delta' => $result->scoreDelta,
            ];
        }, $top);
    }

    /**
     * @return SpyCheck[]
     */
    private function buildChecksFromConfig(): array
    {
        $checks = [];
        $configured = function_exists('config') ? (array) config('spy-detection.checks', []) : [];

        foreach ($configured as $checkClass) {
            if (!is_string($checkClass) || !class_exists($checkClass)) {
                continue;
            }
            $checks[] = app($checkClass);
        }

        return $checks;
    }
}
