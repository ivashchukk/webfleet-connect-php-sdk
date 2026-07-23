<?php

declare(strict_types=1);

namespace Webfleet\Connect\Query;

use InvalidArgumentException;
use Webfleet\Connect\Value\DateRange;
use Webfleet\Connect\Value\ObjectIdentifier;

final readonly class TracksQuery
{
    public function __construct(public ObjectIdentifier $object, public DateRange $range)
    {
        if ($range->durationSeconds() > 172_800) {
            throw new InvalidArgumentException('A showTracks request cannot cover more than 48 hours. Use trackHistory() for longer ranges.');
        }
    }

    /** @return array<string, string> */
    public function toParameters(): array
    {
        return [...$this->object->toParameters(), ...$this->range->toParameters()];
    }
}
