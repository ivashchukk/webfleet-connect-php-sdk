<?php

declare(strict_types=1);

namespace Webfleet\Connect\Query;

use Webfleet\Connect\Value\ObjectIdentifier;

final readonly class ObjectQuery
{
    public function __construct(
        public ?ObjectIdentifier $object = null,
        public ?string $filter = null,
        public ?string $groupName = null,
        public bool $ungroupedOnly = false,
    ) {}

    /** @return array<string, bool|string> */
    public function toParameters(): array
    {
        $parameters = $this->object?->toParameters() ?? [];
        if (null !== $this->filter) {
            $parameters['filterstring'] = $this->filter;
        }
        if ($this->ungroupedOnly) {
            $parameters['ungroupedonly'] = true;
        } elseif (null !== $this->groupName) {
            $parameters['objectgroupname'] = $this->groupName;
        }

        return $parameters;
    }
}
