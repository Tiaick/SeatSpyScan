<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Checks;

use Seat\SpyDetection\Contracts\SpyCheck;
use Seat\SpyDetection\DTO\SpyCheckResult;
use Seat\SpyDetection\DTO\SpyContext;

class CorpAllianceHistoryRiskCheck implements SpyCheck
{
    /** @var int[] */
    private array $negativeCorporationIds;
    /** @var int[] */
    private array $negativeAllianceIds;

    /**
     * @param int[]|null $negativeCorporationIds
     * @param int[]|null $negativeAllianceIds
     */
    public function __construct(?array $negativeCorporationIds = null, ?array $negativeAllianceIds = null)
    {
        $this->negativeCorporationIds = $negativeCorporationIds ?? $this->cfg('negative_corporation_ids');
        $this->negativeAllianceIds = $negativeAllianceIds ?? $this->cfg('negative_alliance_ids');
    }

    public function key(): string
    {
        return 'corp_alliance_history';
    }

    public function description(): string
    {
        return 'Risk from negative corp/alliance history and frequent switches.';
    }

    public function evaluate(SpyContext $ctx): ?SpyCheckResult
    {
        if (empty($ctx->corpHistory)) {
            return null;
        }

        $negativeHits = [];
        $switchCount = 0;
        $recentThreshold = time() - (365 * 86400);

        $byCharacter = [];
        foreach ($ctx->corpHistory as $entry) {
            $charId = $entry['character_id'] ?? null;
            if ($charId === null) {
                continue;
            }
            $byCharacter[$charId][] = $entry;
        }

        foreach ($byCharacter as $entries) {
            usort($entries, static function (array $a, array $b): int {
                return strcmp((string) ($b['start_date'] ?? ''), (string) ($a['start_date'] ?? ''));
            });

            $lastCorp = null;
            foreach ($entries as $entry) {
                $corpId = $entry['corporation_id'] ?? null;
                $allianceId = $entry['alliance_id'] ?? null;
                $startDate = $this->parseDate($entry['start_date'] ?? null);

                if ($corpId !== null && in_array((int) $corpId, $this->negativeCorporationIds, true)) {
                    $negativeHits[] = $entry + ['matched_on' => 'corporation_id'];
                } elseif ($allianceId !== null && in_array((int) $allianceId, $this->negativeAllianceIds, true)) {
                    $negativeHits[] = $entry + ['matched_on' => 'alliance_id'];
                }

                if ($startDate !== null && $startDate >= $recentThreshold) {
                    if ($lastCorp !== null && $corpId !== $lastCorp) {
                        $switchCount++;
                    }
                    $lastCorp = $corpId;
                }
            }
        }

        if (empty($negativeHits) && $switchCount === 0) {
            return null;
        }

        $scoreDelta = min(40, (count($negativeHits) * 6) + ($switchCount >= 4 ? 15 : 0));
        $severity = $this->severityFromScore($scoreDelta);

        $detail = sprintf(
            'Negative corp/alliance hits: %d. Recent switches: %d.',
            count($negativeHits),
            $switchCount
        );

        return new SpyCheckResult(
            $this->key(),
            $severity,
            $scoreDelta,
            'Corp/alliance history risk',
            $detail,
            array_slice($negativeHits, 0, 10)
        );
    }

    private function severityFromScore(int $score): string
    {
        if ($score >= 30) {
            return 'high';
        }
        if ($score >= 15) {
            return 'medium';
        }
        return 'low';
    }

    private function parseDate(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }
        $timestamp = strtotime((string) $value);
        return $timestamp !== false ? $timestamp : null;
    }

    /**
     * @return int[]
     */
    private function cfg(string $key): array
    {
        if (function_exists('config')) {
            return (array) config('spy-detection.' . $key, []);
        }

        return [];
    }
}
