<?php

declare(strict_types=1);

namespace Webfleet\Connect\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Webfleet\Connect\Query\LogbookQuery;
use Webfleet\Connect\Query\TracksQuery;
use Webfleet\Connect\Query\TripQuery;
use Webfleet\Connect\Value\DateRange;
use Webfleet\Connect\Value\DriverIdentifier;
use Webfleet\Connect\Value\ObjectIdentifier;

final class QueryValidationTest extends TestCase
{
    public function testTracksRejectMoreThanFortyEightHours(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TracksQuery(
            ObjectIdentifier::number('V1'),
            $this->range('2026-01-01T00:00:00Z', '2026-01-03T00:00:01Z'),
        );
    }

    public function testDriverTripOverMonthRequiresObjectNumber(): void
    {
        $this->expectException(InvalidArgumentException::class);
        TripQuery::forDriver(
            DriverIdentifier::uid('driver-uid'),
            $this->range('2026-01-01T00:00:00Z', '2026-02-02T00:00:00Z'),
        );
    }

    public function testDriverTripOverMonthAcceptsObjectNumber(): void
    {
        $query = TripQuery::forDriver(
            DriverIdentifier::number('D1'),
            $this->range('2026-01-01T00:00:00Z', '2026-02-02T00:00:00Z'),
            ObjectIdentifier::number('V1'),
        );

        self::assertSame('D1', $query->toParameters()['driverno']);
        self::assertSame('V1', $query->toParameters()['objectno']);
    }

    public function testLogbookTripQueryNeedsNoDateRange(): void
    {
        self::assertSame(['tripid' => '42'], LogbookQuery::forTrip('42')->toParameters());
    }

    public function testDateRangeSerializesAsIso8601Utc(): void
    {
        $range = $this->range('2026-01-01T02:00:00+02:00', '2026-01-01T03:00:00+02:00');

        self::assertSame('2026-01-01T00:00:00+00:00', $range->toParameters()['rangefrom_string']);
        self::assertSame('2026-01-01T01:00:00+00:00', $range->toParameters()['rangeto_string']);
    }

    private function range(string $from, string $to): DateRange
    {
        return new DateRange(new DateTimeImmutable($from), new DateTimeImmutable($to));
    }
}
