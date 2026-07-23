<?php

declare(strict_types=1);

namespace Webfleet\Connect\Contract;

interface RateLimiterInterface
{
    public function acquire(string $action): void;
}
