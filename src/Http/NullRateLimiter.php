<?php

declare(strict_types=1);

namespace Webfleet\Connect\Http;

use Webfleet\Connect\Contract\RateLimiterInterface;

final class NullRateLimiter implements RateLimiterInterface
{
    public function acquire(string $action): void {}
}
