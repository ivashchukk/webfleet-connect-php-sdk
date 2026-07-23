<?php

declare(strict_types=1);

namespace Webfleet\Connect\Dto;

use InvalidArgumentException;

final readonly class GeoPoint
{
    public function __construct(
        public int $latitudeMicrodegrees,
        public int $longitudeMicrodegrees,
    ) {
        if ($latitudeMicrodegrees < -90_000_000 || $latitudeMicrodegrees > 90_000_000) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90 degrees.');
        }
        if ($longitudeMicrodegrees < -180_000_000 || $longitudeMicrodegrees > 180_000_000) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180 degrees.');
        }
    }

    public function latitude(): float
    {
        return $this->latitudeMicrodegrees / 1_000_000;
    }

    public function longitude(): float
    {
        return $this->longitudeMicrodegrees / 1_000_000;
    }
}
