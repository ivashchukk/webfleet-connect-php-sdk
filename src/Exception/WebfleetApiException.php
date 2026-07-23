<?php

declare(strict_types=1);

namespace Webfleet\Connect\Exception;

final class WebfleetApiException extends WebfleetException
{
    public function __construct(
        public readonly string $action,
        public readonly string $apiErrorCode,
        string $message,
        public readonly ?int $httpStatus = null,
    ) {
        parent::__construct(sprintf('Webfleet action %s failed (%s): %s', $action, $apiErrorCode, $message));
    }
}
