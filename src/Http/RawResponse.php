<?php

declare(strict_types=1);

namespace Webfleet\Connect\Http;

final readonly class RawResponse
{
    /**
     * @param array<string, list<string>>  $headers
     * @param list<array<string, mixed>>   $rows
     */
    public function __construct(
        public string $action,
        public int $statusCode,
        public array $headers,
        public array $rows,
    ) {}
}
