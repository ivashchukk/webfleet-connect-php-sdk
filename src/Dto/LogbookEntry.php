<?php

declare(strict_types=1);

namespace Webfleet\Connect\Dto;

use DateTimeImmutable;
use Webfleet\Connect\Internal\ApiValue;

final readonly class LogbookEntry
{
    /** @param array<string, mixed> $source */
    public function __construct(
        public ?string $tripId,
        public ?int $modeCode,
        public ?string $objectNumber,
        public ?string $objectName,
        public ?string $objectUid,
        public ?string $driverNumber,
        public ?string $driverName,
        public ?string $driverUid,
        public ?DateTimeImmutable $startedAt,
        public ?DateTimeImmutable $endedAt,
        public ?int $startOdometerMeters,
        public ?int $endOdometerMeters,
        public ?GeoPoint $startPosition,
        public ?GeoPoint $endPosition,
        public ?string $startLocation,
        public ?string $endLocation,
        public ?int $distanceMeters,
        public ?string $purpose,
        public ?string $contact,
        public ?string $comment,
        public ?DateTimeImmutable $modifiedAt,
        public ?string $modifiedBy,
        public ?int $averageSpeedKph,
        public ?int $maximumSpeedKph,
        public ?float $fuelUsageLitres,
        public ?int $co2Grams,
        public ?float $energyUsageKwh,
        public ?int $startBatteryLevelPercent,
        public ?int $endBatteryLevelPercent,
        public ?float $startBatteryEnergyWh,
        public ?float $endBatteryEnergyWh,
        public array $source,
    ) {}

    public function mode(): ?TripMode
    {
        return null === $this->modeCode ? null : TripMode::tryFrom($this->modeCode);
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            ApiValue::string($row, 'tripid'),
            ApiValue::int($row, 'logflag'),
            ApiValue::string($row, 'objectno'),
            ApiValue::string($row, 'objectname'),
            ApiValue::string($row, 'objectuid'),
            ApiValue::string($row, 'driverno'),
            ApiValue::string($row, 'drivername'),
            ApiValue::string($row, 'driveruid'),
            ApiValue::date($row, 'start_time'),
            ApiValue::date($row, 'end_time'),
            ApiValue::int($row, 'start_odometer'),
            ApiValue::int($row, 'end_odometer'),
            ApiValue::point($row, 'start_latitude', 'start_longitude'),
            ApiValue::point($row, 'end_latitude', 'end_longitude'),
            ApiValue::string($row, 'start_postext'),
            ApiValue::string($row, 'end_postext'),
            ApiValue::int($row, 'distance'),
            ApiValue::string($row, 'logpurpose'),
            ApiValue::string($row, 'logcontact'),
            ApiValue::string($row, 'logcomment'),
            ApiValue::date($row, 'modifiedon'),
            ApiValue::string($row, 'modifiedby'),
            ApiValue::int($row, 'avg_speed'),
            ApiValue::int($row, 'max_speed'),
            ApiValue::float($row, 'fuel_usage'),
            ApiValue::int($row, 'co2'),
            ApiValue::float($row, 'energy_usage'),
            ApiValue::int($row, 'start_battery_level'),
            ApiValue::int($row, 'end_battery_level'),
            ApiValue::float($row, 'start_battery_energy'),
            ApiValue::float($row, 'end_battery_energy'),
            $row,
        );
    }
}
