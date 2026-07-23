<?php

declare(strict_types=1);

namespace Webfleet\Connect\Exception;

final class HttpException extends WebfleetException
{
    public function __construct(
        public readonly string $action,
        public readonly int $statusCode,
    ) {
        parent::__construct(sprintf('Webfleet action %s returned HTTP status %d.', $action, $statusCode));
    }
}
