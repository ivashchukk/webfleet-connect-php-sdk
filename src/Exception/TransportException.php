<?php

declare(strict_types=1);

namespace Webfleet\Connect\Exception;

final class TransportException extends WebfleetException
{
    public readonly string $transportExceptionClass;

    public function __construct(public readonly string $action, \Throwable $exception)
    {
        $this->transportExceptionClass = $exception::class;
        parent::__construct(sprintf('Transport failure while executing Webfleet action %s.', $action));
    }
}
