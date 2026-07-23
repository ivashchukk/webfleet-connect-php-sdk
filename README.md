# Webfleet Connect PHP SDK

An unofficial, typed PHP 8.3+ client for the WEBFLEET.connect CSV/JSON API. The first release focuses on driver trips, logbooks, and historical GPS coordinates.

The SDK follows the current Webfleet authentication model: the username and password are sent with HTTP Basic Auth. They are never added to the request URL. Account and API key remain API query parameters as required by Webfleet.

## Installation

```bash
composer require webfleet-connect/php-sdk
```

## Create a client

```php
use Webfleet\Connect\Credentials;
use Webfleet\Connect\WebfleetClient;

$client = WebfleetClient::create(new Credentials(
    account: 'company-account',
    username: 'api-user',
    password: 'secret',
    apiKey: 'api-key',
));
```

Credentials can also be loaded explicitly from `WEBFLEET_CONNECT_ACCOUNT`, `WEBFLEET_CONNECT_USERNAME`, `WEBFLEET_CONNECT_PASSWORD`, and `WEBFLEET_CONNECT_APIKEY`:

```php
$client = WebfleetClient::create(Credentials::fromEnvironment());
```

## Historical coordinates

Coordinates are preserved in Webfleet's integer microdegree format. `GeoPoint::latitude()` and `longitude()` return regular decimal degrees.

```php
use Webfleet\Connect\Query\TracksQuery;
use Webfleet\Connect\Value\DateRange;
use Webfleet\Connect\Value\ObjectIdentifier;

$object = ObjectIdentifier::number('VEHICLE-01');
$range = new DateRange(
    new DateTimeImmutable('2026-07-20T00:00:00+00:00'),
    new DateTimeImmutable('2026-07-21T00:00:00+00:00'),
);

foreach ($client->tracks(new TracksQuery($object, $range)) as $point) {
    echo $point->recordedAt?->format(DATE_ATOM), ' ',
        $point->position?->latitude(), ', ',
        $point->position?->longitude(), PHP_EOL;
}
```

Webfleet limits one `showTracks` request to 48 hours. For a longer range, use the lazy iterator. It divides requests into valid windows, removes overlap duplicates, and applies the documented request limit:

```php
foreach ($client->trackHistory($object, $longRange) as $point) {
    // Points are fetched and yielded one window at a time.
}
```

## Logbook and driver trips

`showLogbook` contains the current logbook state, including driver information and trip start/end coordinates. `showLogbookHistory` is the audit history of manual edits; it is not GPS history.

```php
use Webfleet\Connect\Query\LogbookHistoryQuery;
use Webfleet\Connect\Query\LogbookQuery;

$entries = $client->logbook(LogbookQuery::forObject($object, $range));
$changes = $client->logbookHistory(LogbookHistoryQuery::forObject($object, $range));
```

For a direct driver-filtered trip report:

```php
use Webfleet\Connect\Query\TripQuery;
use Webfleet\Connect\Value\DriverIdentifier;

$trips = $client->trips(TripQuery::forDriver(
    DriverIdentifier::number('DRIVER-01'),
    $range,
));
```

Driver trip reports are limited to one request per minute. A range over one calendar month also requires an object number. The query objects validate these restrictions before a request is sent.

## Lookups and raw actions

```php
$drivers = $client->drivers();
$objects = $client->fleetObjects();

$raw = $client->request('showStandStills', [
    'objectno' => 'VEHICLE-01',
    ...$range->toParameters(),
]);

foreach ($raw->rows as $row) {
    // Exact Webfleet field names and values.
}
```

The raw escape hatch sends GET requests. It does not provide typed mutation methods or POST payload support in version 0.1.

## HTTP clients and rate limiting

Guzzle is used by `WebfleetClient::create()`. Applications can inject any PSR-18 client and PSR-17 request factory with `WebfleetClient::withHttpClient()`. An optional `RateLimiterInterface` can replace the built-in, per-client rolling-window limiter.

All requested and returned timestamps use ISO-8601 and are normalized to UTC. Missing fields in Webfleet responses become `null`; the original row remains available through each DTO's `source` property.

## Development

```bash
composer install
composer check
```

Tests use mocked PSR responses and never require live credentials. An optional `composer test-integration` command uses the four credential variables plus `WEBFLEET_CONNECT_TEST_OBJECTNO`; it is excluded from the normal test suite.

## License

MIT. This community project is not an official Webfleet product.
