<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Contracts;

use Seat\SpyDetection\DTO\SpyContext;
use Seat\SpyDetection\DTO\SpyCheckResult;

interface SpyCheck
{
    public function key(): string;

    public function description(): string;

    public function evaluate(SpyContext $ctx): ?SpyCheckResult;
}
