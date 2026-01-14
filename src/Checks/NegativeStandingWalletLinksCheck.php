<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Checks;

use Seat\SpyDetection\Contracts\SpyCheck;
use Seat\SpyDetection\DTO\SpyCheckResult;
use Seat\SpyDetection\DTO\SpyContext;

class NegativeStandingWalletLinksCheck implements SpyCheck
{
    /** @var int[] */
    private array $negativeCharacterIds;
    /** @var int[] */
    private array $negativeCorporationIds;
    /** @var int[] */
    private array $negativeAllianceIds;

    /**
     * @param int[]|null $negativeCharacterIds
     * @param int[]|null $negativeCorporationIds
     * @param int[]|null $negativeAllianceIds
     */
    public function __construct(
        ?array $negativeCharacterIds = null,
        ?array $negativeCorporationIds = null,
        ?array $negativeAllianceIds = null
    ) {
        $this->negativeCharacterIds = $negativeCharacterIds ?? $this->cfg('negative_character_ids');
        $this->negativeCorporationIds = $negativeCorporationIds ?? $this->cfg('negative_corporation_ids');
        $this->negativeAllianceIds = $negativeAllianceIds ?? $this->cfg('negative_alliance_ids');
    }

    public function key(): string
    {
        return 'negative_wallet_links';
    }

    public function description(): string
    {
        return 'Wallet interactions with negative-standing entities.';
    }

    public function evaluate(SpyContext $ctx): ?SpyCheckResult
    {
        $hits = [];
        $recentCount = 0;
        $totalAmount = 0.0;
        $now = time();
        $recentThreshold = $now - (30 * 86400);

        foreach ($ctx->walletJournals as $entry) {
            $counterpartyId = $this->extractCounterpartyId($entry);
            if ($counterpartyId === null) {
                continue;
            }

            if (!$this->isNegativeId($counterpartyId)) {
                continue;
            }

            $amount = (float) ($entry['amount'] ?? 0);
            $date = $this->parseDate($entry['date'] ?? null);
            if ($date !== null && $date >= $recentThreshold) {
                $recentCount++;
            }

            $totalAmount += abs($amount);
            $hits[] = [
                'character_id' => $entry['character_id'] ?? null,
                'counterparty_id' => $counterpartyId,
                'amount' => $amount,
                'date' => $entry['date'] ?? null,
                'ref_type' => $entry['ref_type'] ?? null,
                'reason' => $entry['reason'] ?? null,
            ];
        }

        if (empty($hits)) {
            return null;
        }

        usort($hits, static function (array $a, array $b): int {
            return abs((float) $b['amount']) <=> abs((float) $a['amount']);
        });

        $evidence = array_slice($hits, 0, 10);

        $amountScore = min(20, (int) floor(log10(1 + $totalAmount) * 5));
        $frequencyScore = min(20, count($hits) * 2);
        $recencyScore = min(10, $recentCount * 2);
        $scoreDelta = min(50, $amountScore + $frequencyScore + $recencyScore);

        $severity = $this->severityFromScore($scoreDelta);
        $detail = sprintf(
            'Found %d wallet links to negative entities. Total amount %.2f.',
            count($hits),
            $totalAmount
        );

        return new SpyCheckResult(
            $this->key(),
            $severity,
            $scoreDelta,
            'Negative-standing wallet links',
            $detail,
            $evidence
        );
    }

    private function isNegativeId(int $counterpartyId): bool
    {
        return in_array($counterpartyId, $this->negativeCharacterIds, true)
            || in_array($counterpartyId, $this->negativeCorporationIds, true)
            || in_array($counterpartyId, $this->negativeAllianceIds, true);
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function extractCounterpartyId(array $entry): ?int
    {
        $candidates = [
            $entry['second_party_id'] ?? null,
            $entry['first_party_id'] ?? null,
            $entry['counterparty_id'] ?? null,
        ];

        foreach ($candidates as $id) {
            if (is_numeric($id)) {
                return (int) $id;
            }
        }

        return null;
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

    private function severityFromScore(int $score): string
    {
        if ($score >= 40) {
            return 'critical';
        }
        if ($score >= 25) {
            return 'high';
        }
        if ($score >= 15) {
            return 'medium';
        }
        return 'low';
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
