<?php

declare(strict_types=1);

namespace Webfleet\Connect\Query;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Webfleet\Connect\Value\DateRange;
use Webfleet\Connect\Value\ObjectIdentifier;

final readonly class LogbookQuery
{
    private function __construct(
        public ?ObjectIdentifier $object,
        public ?string $tripId,
        public ?DateRange $range,
        public ?DateTimeImmutable $modifiedSince,
    ) {
        if (null !== $range && $range->exceedsCalendarYear()) {
            throw new InvalidArgumentException('A logbook request cannot cover more than one calendar year.');
        }
    }

    public static function forObject(ObjectIdentifier $object, DateRange $range, ?DateTimeImmutable $modifiedSince = null): self
    {
        return new self($object, null, $range, $modifiedSince);
    }

    public static function forTrip(string $tripId): self
    {
        if ('' === trim($tripId)) {
            throw new InvalidArgumentException('Trip ID must not be empty.');
        }

        return new self(null, $tripId, null, null);
    }

    /** @return array<string, string> */
    public function toParameters(): array
    {
        $parameters = null !== $this->tripId
            ? ['tripid' => $this->tripId]
            : [...($this->object?->toParameters() ?? []), ...($this->range?->toParameters() ?? [])];

        if (null !== $this->modifiedSince) {
            $parameters['modified_since'] = $this->modifiedSince
                ->setTimezone(new DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:sP');
        }

        return $parameters;
    }
}
