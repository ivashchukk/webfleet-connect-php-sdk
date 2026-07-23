<?php

declare(strict_types=1);

namespace Webfleet\Connect\Dto;

use DateTimeImmutable;
use Webfleet\Connect\Internal\ApiValue;

final readonly class FleetObject
{
    /** @param array<string, mixed> $source */
    public function __construct(
        public ?string $number,
        public ?string $name,
        public ?string $uid,
        public ?DateTimeImmutable $positionRecordedAt,
        public ?GeoPoint $position,
        public ?int $speedKph,
        public ?int $courseDegrees,
        public ?string $driverNumber,
        public ?string $driverName,
        public ?string $driverUid,
        public array $source,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            ApiValue::string($row, 'objectno'),
            ApiValue::string($row, 'objectname'),
            ApiValue::string($row, 'objectuid'),
            ApiValue::date($row, 'pos_time'),
            ApiValue::point($row, 'latitude_mdeg', 'longitude_mdeg'),
            ApiValue::int($row, 'speed'),
            ApiValue::int($row, 'course'),
            ApiValue::string($row, 'driver'),
            ApiValue::string($row, 'drivername'),
            ApiValue::string($row, 'driveruid'),
            $row,
        );
    }
}
