<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Seat\SpyDetection\Support\RiskScoreMapper;

class RiskScoreMapperTest extends TestCase
{
    public function testRiskMapping(): void
    {
        self::assertSame('low', RiskScoreMapper::mapRiskLevel(0));
        self::assertSame('low', RiskScoreMapper::mapRiskLevel(19));
        self::assertSame('medium', RiskScoreMapper::mapRiskLevel(20));
        self::assertSame('medium', RiskScoreMapper::mapRiskLevel(39));
        self::assertSame('high', RiskScoreMapper::mapRiskLevel(40));
        self::assertSame('high', RiskScoreMapper::mapRiskLevel(69));
        self::assertSame('critical', RiskScoreMapper::mapRiskLevel(70));
        self::assertSame('critical', RiskScoreMapper::mapRiskLevel(100));
    }
}
