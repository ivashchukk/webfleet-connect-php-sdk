<?php

declare(strict_types=1);

namespace Webfleet\Connect\Query;

use InvalidArgumentException;
use Webfleet\Connect\Value\DateRange;
use Webfleet\Connect\Value\ObjectIdentifier;

final readonly class LogbookHistoryQuery
{
    private function __construct(
        public ?ObjectIdentifier $object,
        public ?string $tripId,
        public ?DateRange $range,
    ) {
        if (null !== $range && $range->exceedsCalendarYear()) {
            throw new InvalidArgumentException('A logbook history request cannot cover more than one calendar year.');
        }
    }

    public static function forObject(ObjectIdentifier $object, DateRange $range): self
    {
        return new self($object, null, $range);
    }

    public static function forTrip(string $tripId): self
    {
        if ('' === trim($tripId)) {
            throw new InvalidArgumentException('Trip ID must not be empty.');
        }

        return new self(null, $tripId, null);
    }

    /** @return array<string, string> */
    public function toParameters(): array
    {
        return null !== $this->tripId
            ? ['tripid' => $this->tripId]
            : [...($this->object?->toParameters() ?? []), ...($this->range?->toParameters() ?? [])];
    }
}
