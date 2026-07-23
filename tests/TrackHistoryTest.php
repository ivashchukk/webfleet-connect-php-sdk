<?php

declare(strict_types=1);

namespace Webfleet\Connect\Tests;

use DateTimeImmutable;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Webfleet\Connect\ClientOptions;
use Webfleet\Connect\Credentials;
use Webfleet\Connect\Http\NullRateLimiter;
use Webfleet\Connect\Tests\Support\QueueHttpClient;
use Webfleet\Connect\Value\DateRange;
use Webfleet\Connect\Value\ObjectIdentifier;
use Webfleet\Connect\WebfleetClient;

final class TrackHistoryTest extends TestCase
{
    public function testLazilyChunksSortsAndDeduplicatesBoundaries(): void
    {
        $boundary = [
            'pos_time' => '2026-01-03T00:00:00Z',
            'receivetime' => '2026-01-03T00:00:01Z',
            'latitude' => 50000000,
            'longitude' => 30000000,
            'speed' => 10,
            'course' => 90,
            'odometer' => 200,
        ];
        $first = [
            $boundary,
            [...$boundary, 'pos_time' => '2026-01-01T01:00:00Z', 'odometer' => 100],
        ];
        $second = [
            $boundary,
            [...$boundary, 'pos_time' => '2026-01-04T01:00:00Z', 'odometer' => 300],
        ];
        $http = new QueueHttpClient(
            new Response(200, [], json_encode($first, JSON_THROW_ON_ERROR)),
            new Response(200, [], json_encode($second, JSON_THROW_ON_ERROR)),
        );
        $client = WebfleetClient::withHttpClient(
            new Credentials('account', 'user', 'password', 'key'),
            $http,
            new HttpFactory(),
            new ClientOptions(rateLimiting: false),
            new NullRateLimiter(),
        );
        $history = $client->trackHistory(
            ObjectIdentifier::number('V1'),
            new DateRange(new DateTimeImmutable('2026-01-01T00:00:00Z'), new DateTimeImmutable('2026-01-05T00:00:00Z')),
        );

        self::assertCount(0, $http->requests, 'The generator must not request data until iteration starts.');
        $points = iterator_to_array($history, false);

        self::assertCount(2, $http->requests);
        self::assertCount(3, $points);
        self::assertSame([100, 200, 300], array_map(static fn($point): ?int => $point->odometerMeters, $points));
        self::assertStringContainsString('rangefrom_string=2026-01-01T00%3A00%3A00%2B00%3A00', $http->requests[0]->getUri()->getQuery());
        self::assertStringContainsString('rangefrom_string=2026-01-03T00%3A00%3A00%2B00%3A00', $http->requests[1]->getUri()->getQuery());
    }
}
