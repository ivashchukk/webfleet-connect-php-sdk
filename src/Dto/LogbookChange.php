<?php

declare(strict_types=1);

namespace Webfleet\Connect\Dto;

use DateTimeImmutable;
use Webfleet\Connect\Internal\ApiValue;

final readonly class LogbookChange
{
    /** @param array<string, mixed> $source */
    public function __construct(
        public ?string $tripId,
        public ?string $objectNumber,
        public ?string $objectName,
        public ?string $objectUid,
        public ?string $oldPurpose,
        public ?string $newPurpose,
        public ?string $oldContact,
        public ?string $newContact,
        public ?string $oldComment,
        public ?string $newComment,
        public ?int $oldModeCode,
        public ?int $newModeCode,
        public ?DateTimeImmutable $modifiedAt,
        public ?string $modifiedBy,
        public ?string $reason,
        public array $source,
    ) {}

    public function oldMode(): ?TripMode
    {
        return null === $this->oldModeCode ? null : TripMode::tryFrom($this->oldModeCode);
    }

    public function newMode(): ?TripMode
    {
        return null === $this->newModeCode ? null : TripMode::tryFrom($this->newModeCode);
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            ApiValue::string($row, 'tripid'),
            ApiValue::string($row, 'objectno'),
            ApiValue::string($row, 'objectname'),
            ApiValue::string($row, 'objectuid'),
            ApiValue::string($row, 'logpurposeold'),
            ApiValue::string($row, 'logpurposenew'),
            ApiValue::string($row, 'logcontactold'),
            ApiValue::string($row, 'logcontactnew'),
            ApiValue::string($row, 'logcommentold'),
            ApiValue::string($row, 'logcommentnew'),
            ApiValue::int($row, 'logflagold'),
            ApiValue::int($row, 'logflagnew'),
            ApiValue::date($row, 'modifiedon'),
            ApiValue::string($row, 'modifiedby'),
            ApiValue::string($row, 'reason'),
            $row,
        );
    }
}
