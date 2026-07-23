<?php

declare(strict_types=1);

namespace Webfleet\Connect\Http;

use Closure;
use InvalidArgumentException;
use Webfleet\Connect\Contract\RateLimiterInterface;

final class SlidingWindowRateLimiter implements RateLimiterInterface
{
    /** @var array<string, positive-int> */
    private const DEFAULT_LIMITS = [
        'showTracks' => 10,
        'showLogbook' => 10,
        'showLogbookHistory' => 10,
        'showTripReportExtern' => 1,
        'showDriverReportExtern' => 10,
        'showObjectReportExtern' => 6,
    ];

    /** @var array<string, list<float>> */
    private array $requests = [];

    /** @var Closure(): float */
    private Closure $clock;

    /** @var Closure(int): void */
    private Closure $sleep;

    /**
     * @param array<string, int>          $limits
     * @param null|callable(): float       $clock  Monotonic seconds
     * @param null|callable(int): void     $sleep  Microseconds
     */
    public function __construct(
        private readonly array $limits = self::DEFAULT_LIMITS,
        ?callable $clock = null,
        ?callable $sleep = null,
        private readonly float $windowSeconds = 60.0,
    ) {
        if ($windowSeconds <= 0) {
            throw new InvalidArgumentException('The rate-limit window must be greater than zero.');
        }
        foreach ($limits as $action => $limit) {
            if ('' === $action || $limit < 1) {
                throw new InvalidArgumentException('Rate limits require a non-empty action and a positive request count.');
            }
        }

        $this->clock = null === $clock
            ? static fn(): float => hrtime(true) / 1_000_000_000
            : Closure::fromCallable($clock);
        $this->sleep = null === $sleep
            ? static function (int $microseconds): void {
                usleep($microseconds);
            }
        : Closure::fromCallable($sleep);
    }

    public function acquire(string $action): void
    {
        $limit = $this->limits[$action] ?? null;
        if (null === $limit) {
            return;
        }

        $now = ($this->clock)();
        $requests = array_values(array_filter(
            $this->requests[$action] ?? [],
            fn(float $timestamp): bool => $timestamp > $now - $this->windowSeconds,
        ));

        if (count($requests) >= $limit) {
            $waitSeconds = max(0.0, $this->windowSeconds - ($now - $requests[0]));
            if ($waitSeconds > 0) {
                ($this->sleep)((int) ceil($waitSeconds * 1_000_000));
            }

            $now = ($this->clock)();
            $requests = array_values(array_filter(
                $requests,
                fn(float $timestamp): bool => $timestamp > $now - $this->windowSeconds,
            ));
        }

        $requests[] = $now;
        $this->requests[$action] = $requests;
    }
}
