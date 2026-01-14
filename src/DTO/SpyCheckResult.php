<?php

declare(strict_types=1);

namespace Seat\SpyDetection\DTO;

class SpyCheckResult
{
    public string $key;
    public string $severity;
    public int $scoreDelta;
    public string $title;
    public string $detail;
    /** @var array<int, array<string, mixed>> */
    public array $evidence;

    /**
     * @param array<int, array<string, mixed>> $evidence
     */
    public function __construct(
        string $key,
        string $severity,
        int $scoreDelta,
        string $title,
        string $detail,
        array $evidence = []
    ) {
        $this->key = $key;
        $this->severity = $severity;
        $this->scoreDelta = $scoreDelta;
        $this->title = $title;
        $this->detail = $detail;
        $this->evidence = $evidence;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'severity' => $this->severity,
            'score_delta' => $this->scoreDelta,
            'title' => $this->title,
            'detail' => $this->detail,
            'evidence' => $this->evidence,
        ];
    }
}
