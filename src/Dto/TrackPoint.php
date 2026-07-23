<?php

declare(strict_types=1);

namespace Webfleet\Connect\Dto;

use DateTimeImmutable;
use Webfleet\Connect\Internal\ApiValue;

final readonly class TrackPoint
{
    /** @param array<string, mixed> $source */
    public function __construct(
        public ?DateTimeImmutable $recordedAt,
        public ?DateTimeImmutable $receivedAt,
        public ?GeoPoint $position,
        public ?int $speedKph,
        public ?int $courseDegrees,
        public ?float $fuelUsageLitres,
        public ?int $odometerMeters,
        public ?string $country,
        public ?string $state,
        public ?float $energyUsageKwh,
        public array $source,
    ) {}

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            ApiValue::date($row, 'pos_time'),
            ApiValue::date($row, 'receivetime'),
            ApiValue::point($row, 'latitude', 'longitude'),
            ApiValue::int($row, 'speed'),
            ApiValue::int($row, 'course'),
            ApiValue::float($row, 'fuelusage'),
            ApiValue::int($row, 'odometer'),
            ApiValue::string($row, 'country'),
            ApiValue::string($row, 'state'),
            ApiValue::float($row, 'energy_usage'),
            $row,
        );
    }

    public function fingerprint(): string
    {
        return implode('|', [
            $this->recordedAt?->format('U.u') ?? '',
            $this->receivedAt?->format('U.u') ?? '',
            null === $this->position ? '' : (string) $this->position->latitudeMicrodegrees,
            null === $this->position ? '' : (string) $this->position->longitudeMicrodegrees,
            (string) ($this->speedKph ?? ''),
            (string) ($this->courseDegrees ?? ''),
            (string) ($this->odometerMeters ?? ''),
        ]);
    }
}
