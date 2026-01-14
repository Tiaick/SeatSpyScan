<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Support;

class RiskScoreMapper
{
    public static function mapRiskLevel(int $score): string
    {
        if ($score >= 70) {
            return 'critical';
        }
        if ($score >= 40) {
            return 'high';
        }
        if ($score >= 20) {
            return 'medium';
        }
        return 'low';
    }
}
