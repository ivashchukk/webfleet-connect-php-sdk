<?php

declare(strict_types=1);

namespace Webfleet\Connect\Dto;

use DateTimeImmutable;
use Webfleet\Connect\Internal\ApiValue;

final readonly class Driver
{
    /** @param array<string, mixed> $source */
    public function __construct(
        public ?string $number,
        public ?string $name1,
        public ?string $name2,
        public ?string $name3,
        public ?string $uid,
        public ?string $currentObjectNumber,
        public ?string $currentObjectUid,
        public ?DateTimeImmutable $signedOnAt,
        public ?int $currentWorkStateCode,
        public ?GeoPoint $addressPosition,
        public array $source,
    ) {}

    public function displayName(): string
    {
        return implode(' ', array_filter([$this->name1, $this->name2, $this->name3], static fn(?string $part): bool => null !== $part));
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            ApiValue::string($row, 'driverno'),
            ApiValue::string($row, 'name1'),
            ApiValue::string($row, 'name2'),
            ApiValue::string($row, 'name3'),
            ApiValue::string($row, 'driveruid'),
            ApiValue::string($row, 'objectno'),
            ApiValue::string($row, 'objectuid'),
            ApiValue::date($row, 'signontime'),
            ApiValue::int($row, 'current_workstate'),
            ApiValue::point($row, 'addr_latitude', 'addr_longitude'),
            $row,
        );
    }
}
