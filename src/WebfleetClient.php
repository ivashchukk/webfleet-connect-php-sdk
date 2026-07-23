<?php

declare(strict_types=1);

namespace Webfleet\Connect;

use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Throwable;
use Webfleet\Connect\Contract\RateLimiterInterface;
use Webfleet\Connect\Dto\Driver;
use Webfleet\Connect\Dto\FleetObject;
use Webfleet\Connect\Dto\LogbookChange;
use Webfleet\Connect\Dto\LogbookEntry;
use Webfleet\Connect\Dto\TrackPoint;
use Webfleet\Connect\Dto\Trip;
use Webfleet\Connect\Exception\UnexpectedResponseException;
use Webfleet\Connect\Http\ApiTransport;
use Webfleet\Connect\Http\NullRateLimiter;
use Webfleet\Connect\Http\RawResponse;
use Webfleet\Connect\Http\SlidingWindowRateLimiter;
use Webfleet\Connect\Query\DriverQuery;
use Webfleet\Connect\Query\LogbookHistoryQuery;
use Webfleet\Connect\Query\LogbookQuery;
use Webfleet\Connect\Query\ObjectQuery;
use Webfleet\Connect\Query\TracksQuery;
use Webfleet\Connect\Query\TripQuery;
use Webfleet\Connect\Value\DateRange;
use Webfleet\Connect\Value\ObjectIdentifier;

final readonly class WebfleetClient
{
    private function __construct(private ApiTransport $transport) {}

    public static function create(Credentials $credentials, ?ClientOptions $options = null): self
    {
        $options ??= new ClientOptions();
        $client = new Client(['timeout' => $options->timeoutSeconds, 'http_errors' => false]);
        $factory = new HttpFactory();

        return self::withHttpClient($credentials, $client, $factory, $options);
    }

    public static function withHttpClient(
        Credentials $credentials,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        ?ClientOptions $options = null,
        ?RateLimiterInterface $rateLimiter = null,
    ): self {
        $options ??= new ClientOptions();
        $rateLimiter ??= $options->rateLimiting ? new SlidingWindowRateLimiter() : new NullRateLimiter();

        return new self(new ApiTransport($credentials, $options, $httpClient, $requestFactory, $rateLimiter));
    }

    /** @return list<TrackPoint> */
    public function tracks(TracksQuery $query): array
    {
        return $this->map('showTracks', $query->toParameters(), TrackPoint::fromArray(...));
    }

    /** @return Generator<int, TrackPoint> */
    public function trackHistory(ObjectIdentifier $object, DateRange $range): Generator
    {
        $cursor = $range->from;
        /** @var array<string, true> $previousFingerprints */
        $previousFingerprints = [];

        while ($cursor < $range->to) {
            $candidateEnd = $cursor->modify('+48 hours');
            $end = $candidateEnd < $range->to ? $candidateEnd : $range->to;
            $points = $this->tracks(new TracksQuery($object, new DateRange($cursor, $end)));

            usort($points, static function (TrackPoint $left, TrackPoint $right): int {
                $leftRecorded = $left->recordedAt?->format('U.u') ?? '';
                $rightRecorded = $right->recordedAt?->format('U.u') ?? '';
                $recordedComparison = $leftRecorded <=> $rightRecorded;

                return 0 !== $recordedComparison
                    ? $recordedComparison
                    : (($left->receivedAt?->format('U.u') ?? '') <=> ($right->receivedAt?->format('U.u') ?? ''));
            });

            $currentFingerprints = [];
            foreach ($points as $point) {
                $fingerprint = $point->fingerprint();
                $currentFingerprints[$fingerprint] = true;
                if (!isset($previousFingerprints[$fingerprint])) {
                    yield $point;
                }
            }

            $previousFingerprints = $currentFingerprints;
            $cursor = $end;
        }
    }

    /** @return list<LogbookEntry> */
    public function logbook(LogbookQuery $query): array
    {
        return $this->map('showLogbook', $query->toParameters(), LogbookEntry::fromArray(...));
    }

    /** @return list<LogbookChange> */
    public function logbookHistory(LogbookHistoryQuery $query): array
    {
        return $this->map('showLogbookHistory', $query->toParameters(), LogbookChange::fromArray(...));
    }

    /** @return list<Trip> */
    public function trips(TripQuery $query): array
    {
        return $this->map('showTripReportExtern', $query->toParameters(), Trip::fromArray(...));
    }

    /** @return list<Driver> */
    public function drivers(?DriverQuery $query = null): array
    {
        return $this->map('showDriverReportExtern', ($query ?? new DriverQuery())->toParameters(), Driver::fromArray(...));
    }

    /** @return list<FleetObject> */
    public function fleetObjects(?ObjectQuery $query = null): array
    {
        return $this->map('showObjectReportExtern', ($query ?? new ObjectQuery())->toParameters(), FleetObject::fromArray(...));
    }

    /**
     * @param array<string, null|bool|float|int|string|\Stringable|list<bool|float|int|string|\Stringable>> $parameters
     */
    public function request(string $action, array $parameters = []): RawResponse
    {
        return $this->transport->request($action, $parameters);
    }

    /**
     * @template T of object
     * @param array<string, bool|float|int|string> $parameters
     * @param callable(array<string, mixed>): T   $hydrate
     * @return list<T>
     */
    private function map(string $action, array $parameters, callable $hydrate): array
    {
        $response = $this->transport->request($action, $parameters);
        $items = [];
        foreach ($response->rows as $row) {
            try {
                $items[] = $hydrate($row);
            } catch (Throwable $exception) {
                throw new UnexpectedResponseException($action, 'a result row could not be hydrated (' . $exception::class . ')');
            }
        }

        return $items;
    }
}
