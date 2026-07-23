<?php

declare(strict_types=1);

namespace Webfleet\Connect\Query;

use Webfleet\Connect\Value\DriverIdentifier;

final readonly class DriverQuery
{
    public function __construct(
        public ?DriverIdentifier $driver = null,
        public ?string $filter = null,
        public ?string $groupName = null,
        public bool $ungroupedOnly = false,
    ) {}

    /** @return array<string, bool|string> */
    public function toParameters(): array
    {
        $parameters = $this->driver?->toParameters() ?? [];
        if (null !== $this->filter) {
            $parameters['filterstring'] = $this->filter;
        }
        if ($this->ungroupedOnly) {
            $parameters['ungroupedonly'] = true;
        } elseif (null !== $this->groupName) {
            $parameters['drivergroupname'] = $this->groupName;
        }

        return $parameters;
    }
}
