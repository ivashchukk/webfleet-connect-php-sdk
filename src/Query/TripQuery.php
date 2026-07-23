<?php

declare(strict_types=1);

namespace Webfleet\Connect\Query;

use InvalidArgumentException;
use Webfleet\Connect\Value\DateRange;
use Webfleet\Connect\Value\DriverIdentifier;
use Webfleet\Connect\Value\ObjectIdentifier;

final readonly class TripQuery
{
    private function __construct(
        public ?DateRange $range,
        public ?string $afterTripId,
        public ?ObjectIdentifier $object,
        public ?DriverIdentifier $driver,
        public ?string $driverGroupName = null,
        public ?string $objectGroupName = null,
    ) {
        if (null !== $range && $range->exceedsCalendarYear()) {
            throw new InvalidArgumentException('A trip report cannot cover more than one calendar year.');
        }

        if (null !== $range && $range->exceedsCalendarMonth() && null === $object?->number) {
            throw new InvalidArgumentException('Trip report ranges over one calendar month require an object number.');
        }
    }

    public static function forDriver(DriverIdentifier $driver, DateRange $range, ?ObjectIdentifier $object = null): self
    {
        return new self($range, null, $object, $driver);
    }

    public static function forObject(ObjectIdentifier $object, DateRange $range): self
    {
        return new self($range, null, $object, null);
    }

    public static function sinceTrip(
        string $tripId,
        ?ObjectIdentifier $object = null,
        ?DriverIdentifier $driver = null,
    ): self {
        if ('' === trim($tripId)) {
            throw new InvalidArgumentException('Trip ID must not be empty.');
        }

        return new self(null, $tripId, $object, $driver);
    }

    public static function forGroups(DateRange $range, ?string $driverGroupName = null, ?string $objectGroupName = null): self
    {
        if (null === $driverGroupName && null === $objectGroupName) {
            throw new InvalidArgumentException('At least one group name must be supplied.');
        }

        return new self($range, null, null, null, $driverGroupName, $objectGroupName);
    }

    /** @return array<string, string> */
    public function toParameters(): array
    {
        $parameters = null !== $this->afterTripId
            ? ['tripid' => $this->afterTripId]
            : ($this->range?->toParameters() ?? []);

        if (null !== $this->object) {
            $parameters = [...$parameters, ...$this->object->toParameters()];
        }
        if (null !== $this->driver) {
            $parameters = [...$parameters, ...$this->driver->toParameters()];
        }
        if (null !== $this->driverGroupName) {
            $parameters['drivergroupname'] = $this->driverGroupName;
        }
        if (null !== $this->objectGroupName) {
            $parameters['objectgroupname'] = $this->objectGroupName;
        }

        return $parameters;
    }
}
