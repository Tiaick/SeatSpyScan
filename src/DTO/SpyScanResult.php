<?php

declare(strict_types=1);

namespace Seat\SpyDetection\DTO;

class SpyScanResult
{
    public int $score;
    public string $riskLevel;
    /** @var SpyCheckResult[] */
    public array $findings;
    /** @var array<int, array<string, mixed>> */
    public array $summary;
    /** @var array<string, mixed> */
    public array $meta;

    /**
     * @param SpyCheckResult[] $findings
     * @param array<int, array<string, mixed>> $summary
     * @param array<string, mixed> $meta
     */
    public function __construct(int $score, string $riskLevel, array $findings, array $summary, array $meta = [])
    {
        $this->score = $score;
        $this->riskLevel = $riskLevel;
        $this->findings = $findings;
        $this->summary = $summary;
        $this->meta = $meta;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score' => $this->score,
            'risk_level' => $this->riskLevel,
            'findings' => array_map(static fn (SpyCheckResult $r) => $r->toArray(), $this->findings),
            'summary' => $this->summary,
            'meta' => $this->meta,
        ];
    }
}
