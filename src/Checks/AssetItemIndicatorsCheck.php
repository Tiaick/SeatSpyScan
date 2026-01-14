<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Checks;

use Seat\SpyDetection\Contracts\SpyCheck;
use Seat\SpyDetection\DTO\SpyCheckResult;
use Seat\SpyDetection\DTO\SpyContext;

class AssetItemIndicatorsCheck implements SpyCheck
{
    /** @var int[] */
    private array $suspiciousTypeIds;
    /** @var int[] */
    private array $suspiciousGroupIds;
    /** @var int[] */
    private array $suspiciousCategoryIds;

    /**
     * @param int[]|null $suspiciousTypeIds
     * @param int[]|null $suspiciousGroupIds
     * @param int[]|null $suspiciousCategoryIds
     */
    public function __construct(
        ?array $suspiciousTypeIds = null,
        ?array $suspiciousGroupIds = null,
        ?array $suspiciousCategoryIds = null
    ) {
        $this->suspiciousTypeIds = $suspiciousTypeIds ?? $this->cfg('suspicious_type_ids');
        $this->suspiciousGroupIds = $suspiciousGroupIds ?? $this->cfg('suspicious_group_ids');
        $this->suspiciousCategoryIds = $suspiciousCategoryIds ?? $this->cfg('suspicious_category_ids');
    }

    public function key(): string
    {
        return 'asset_item_indicators';
    }

    public function description(): string
    {
        return 'Suspicious assets or items found in character inventories.';
    }

    public function evaluate(SpyContext $ctx): ?SpyCheckResult
    {
        $evidence = [];

        foreach ($ctx->assets as $asset) {
            $typeId = $asset['type_id'] ?? null;
            $groupId = $asset['group_id'] ?? null;
            $categoryId = $asset['category_id'] ?? null;

            $matched = false;
            $matchedOn = null;

            if ($typeId !== null && in_array((int) $typeId, $this->suspiciousTypeIds, true)) {
                $matched = true;
                $matchedOn = 'type_id';
            } elseif ($groupId !== null && in_array((int) $groupId, $this->suspiciousGroupIds, true)) {
                $matched = true;
                $matchedOn = 'group_id';
            } elseif ($categoryId !== null && in_array((int) $categoryId, $this->suspiciousCategoryIds, true)) {
                $matched = true;
                $matchedOn = 'category_id';
            }

            if (!$matched) {
                continue;
            }

            $evidence[] = [
                'character_id' => $asset['character_id'] ?? null,
                'type_id' => $typeId,
                'group_id' => $groupId,
                'category_id' => $categoryId,
                'quantity' => $asset['quantity'] ?? null,
                'location_id' => $asset['location_id'] ?? null,
                'location_type' => $asset['location_type'] ?? null,
                'matched_on' => $matchedOn,
            ];
        }

        if (empty($evidence)) {
            return null;
        }

        $count = count($evidence);
        $scoreDelta = min(30, $count * 3);
        $severity = $this->severityFromCount($count);

        $detail = sprintf('Detected %d suspicious assets/items.', $count);

        return new SpyCheckResult(
            $this->key(),
            $severity,
            $scoreDelta,
            'Asset/item indicators',
            $detail,
            array_slice($evidence, 0, 20)
        );
    }

    private function severityFromCount(int $count): string
    {
        if ($count >= 8) {
            return 'high';
        }
        if ($count >= 4) {
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
