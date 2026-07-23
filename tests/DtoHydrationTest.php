<?php

declare(strict_types=1);

namespace Webfleet\Connect\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Webfleet\Connect\ClientOptions;
use Webfleet\Connect\Credentials;
use Webfleet\Connect\Dto\TripMode;
use Webfleet\Connect\Http\NullRateLimiter;
use Webfleet\Connect\Query\DriverQuery;
use Webfleet\Connect\Query\LogbookQuery;
use Webfleet\Connect\Query\ObjectQuery;
use Webfleet\Connect\Query\TracksQuery;
use Webfleet\Connect\Tests\Support\QueueHttpClient;
use Webfleet\Connect\Value\DateRange;
use Webfleet\Connect\Value\ObjectIdentifier;
use Webfleet\Connect\WebfleetClient;

final class DtoHydrationTest extends TestCase
{
    public function testHydratesTrackCoordinatesAndUtcTimes(): void
    {
        $row = [[
            'pos_time' => '2026-07-20T15:00:00+03:00',
            'receivetime' => '2026-07-20T12:00:05Z',
            'latitude' => 50450123,
            'longitude' => 30523123,
            'speed' => 72,
            'course' => 180,
            'odometer' => 123456,
        ]];
        $client = $this->client(new Response(200, [], json_encode($row, JSON_THROW_ON_ERROR)));
        $range = new DateRange(new \DateTimeImmutable('2026-07-20T00:00:00Z'), new \DateTimeImmutable('2026-07-21T00:00:00Z'));

        $points = $client->tracks(new TracksQuery(ObjectIdentifier::number('V1'), $range));

        self::assertCount(1, $points);
        self::assertSame('2026-07-20T12:00:00+00:00', $points[0]->recordedAt?->format('c'));
        self::assertNotNull($points[0]->position);
        self::assertSame(50.450123, $points[0]->position->latitude());
        self::assertSame(30.523123, $points[0]->position->longitude());
        self::assertSame(72, $points[0]->speedKph);
    }

    public function testHydratesNullableLogbookFieldsAndPreservesUnknownMode(): void
    {
        $row = [[
            'tripid' => '9007199254740999',
            'logflag' => 99,
            'objectno' => 'V1',
            'driverno' => 'D1',
            'start_time' => '2026-07-20T10:00:00Z',
            'end_time' => '2026-07-20T11:00:00Z',
            'start_latitude' => 50450000,
            'start_longitude' => 30523000,
            'custom_future_field' => 'kept',
        ]];
        $client = $this->client(new Response(200, [], json_encode($row, JSON_THROW_ON_ERROR)));

        $entries = $client->logbook(LogbookQuery::forTrip('9007199254740999'));

        self::assertSame('9007199254740999', $entries[0]->tripId);
        self::assertSame(99, $entries[0]->modeCode);
        self::assertNull($entries[0]->mode());
        self::assertNull($entries[0]->endPosition);
        self::assertSame('kept', $entries[0]->source['custom_future_field']);
    }

    public function testKnownTripModeUsesEnum(): void
    {
        $row = [['tripid' => '1', 'logflag' => 2]];
        $client = $this->client(new Response(200, [], json_encode($row, JSON_THROW_ON_ERROR)));

        $entry = $client->logbook(LogbookQuery::forTrip('1'))[0];

        self::assertSame(TripMode::Business, $entry->mode());
    }

    public function testHydratesLookupFieldNamesFromWebfleetReference(): void
    {
        $driverClient = $this->client(new Response(200, [], json_encode([[
            'driverno' => 'D1',
            'name1' => 'Ada',
            'name2' => 'Lovelace',
            'driveruid' => 'driver-uid',
            'objectno' => 'V1',
            'current_workstate' => 5,
            'addr_latitude' => 50450000,
            'addr_longitude' => 30523000,
        ]], JSON_THROW_ON_ERROR)));
        $driver = $driverClient->drivers(new DriverQuery())[0];

        self::assertSame('Ada Lovelace', $driver->displayName());
        self::assertSame(5, $driver->currentWorkStateCode);
        self::assertSame(50.45, $driver->addressPosition?->latitude());

        $objectClient = $this->client(new Response(200, [], json_encode([[
            'objectno' => 'V1',
            'objectname' => 'Truck 1',
            'latitude_mdeg' => 50450000,
            'longitude_mdeg' => 30523000,
            'driver' => 'D1',
            'drivername' => 'Ada Lovelace',
        ]], JSON_THROW_ON_ERROR)));
        $object = $objectClient->fleetObjects(new ObjectQuery())[0];

        self::assertSame(30.523, $object->position?->longitude());
        self::assertSame('D1', $object->driverNumber);
    }

    private function client(Response $response): WebfleetClient
    {
        return WebfleetClient::withHttpClient(
            new Credentials('account', 'user', 'password', 'key'),
            new QueueHttpClient($response),
            new HttpFactory(),
            new ClientOptions(rateLimiting: false),
            new NullRateLimiter(),
        );
    }
}
