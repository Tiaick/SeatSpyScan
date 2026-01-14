<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Seat\SpyDetection\Checks\AssetItemIndicatorsCheck;
use Seat\SpyDetection\DTO\SpyContext;

class AssetItemIndicatorsCheckTest extends TestCase
{
    public function testDetectsSuspiciousAssets(): void
    {
        $check = new AssetItemIndicatorsCheck([1154], [], []);

        $ctx = new SpyContext(
            1,
            [123],
            123,
            'Test Char',
            1,
            [],
            [
                [
                    'character_id' => 123,
                    'type_id' => 1154,
                    'group_id' => 20,
                    'category_id' => 6,
                    'quantity' => 5,
                ],
                [
                    'character_id' => 123,
                    'type_id' => 2000,
                    'group_id' => 999,
                    'category_id' => 10,
                    'quantity' => 1,
                ],
            ]
        );

        $result = $check->evaluate($ctx);

        self::assertNotNull($result);
        self::assertSame('asset_item_indicators', $result->key);
        self::assertGreaterThan(0, $result->scoreDelta);
        self::assertCount(1, $result->evidence);
    }
}
