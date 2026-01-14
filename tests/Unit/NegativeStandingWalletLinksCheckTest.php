<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Seat\SpyDetection\Checks\NegativeStandingWalletLinksCheck;
use Seat\SpyDetection\DTO\SpyContext;

class NegativeStandingWalletLinksCheckTest extends TestCase
{
    public function testDetectsNegativeWalletLinks(): void
    {
        $check = new NegativeStandingWalletLinksCheck([999], [], []);

        $ctx = new SpyContext(
            1,
            [123],
            123,
            'Test Char',
            1,
            [
                [
                    'character_id' => 123,
                    'amount' => -5000000,
                    'date' => '2024-01-01 00:00:00',
                    'second_party_id' => 999,
                    'ref_type' => 'player_donation',
                ],
                [
                    'character_id' => 123,
                    'amount' => 2000000,
                    'date' => '2024-01-02 00:00:00',
                    'second_party_id' => 999,
                    'ref_type' => 'market_transaction',
                ],
            ]
        );

        $result = $check->evaluate($ctx);

        self::assertNotNull($result);
        self::assertGreaterThan(0, $result->scoreDelta);
        self::assertSame('negative_wallet_links', $result->key);
        self::assertNotEmpty($result->evidence);
    }
}
