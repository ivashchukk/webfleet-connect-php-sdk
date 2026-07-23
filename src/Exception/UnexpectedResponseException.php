<?php

declare(strict_types=1);

namespace Webfleet\Connect\Exception;

final class UnexpectedResponseException extends WebfleetException
{
    public function __construct(public readonly string $action, string $reason)
    {
        parent::__construct(sprintf('Unexpected response for Webfleet action %s: %s', $action, $reason));
    }
}
