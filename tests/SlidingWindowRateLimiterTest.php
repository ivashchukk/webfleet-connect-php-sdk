<?php

declare(strict_types=1);

namespace Webfleet\Connect\Tests;

use PHPUnit\Framework\TestCase;
use Webfleet\Connect\Http\SlidingWindowRateLimiter;

final class SlidingWindowRateLimiterTest extends TestCase
{
    public function testWaitsWhenKnownActionReachesLimit(): void
    {
        $time = 100.0;
        $sleeps = [];
        $limiter = new SlidingWindowRateLimiter(
            ['showTracks' => 2],
            static function () use (&$time): float {
                return $time;
            },
            static function (int $microseconds) use (&$time, &$sleeps): void {
                $sleeps[] = $microseconds;
                $time += $microseconds / 1_000_000;
            },
        );

        $limiter->acquire('showTracks');
        $time += 1.0;
        $limiter->acquire('showTracks');
        $time += 1.0;
        $limiter->acquire('showTracks');

        self::assertSame([58_000_000], $sleeps);
    }

    public function testUnknownActionsAreNotDelayed(): void
    {
        $slept = false;
        $limiter = new SlidingWindowRateLimiter(
            [],
            static fn(): float => 100.0,
            static function () use (&$slept): void {
                $slept = true;
            },
        );

        $limiter->acquire('unknownAction');

        self::assertFalse($slept);
    }
}
