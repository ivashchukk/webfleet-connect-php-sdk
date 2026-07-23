<?php

declare(strict_types=1);

namespace Webfleet\Connect;

use InvalidArgumentException;

final readonly class ClientOptions
{
    public function __construct(
        public string $language = 'en',
        public float $timeoutSeconds = 30.0,
        public bool $rateLimiting = true,
    ) {
        if (!in_array($language, ['en', 'de'], true)) {
            throw new InvalidArgumentException('Language must be either "en" or "de".');
        }

        if ($timeoutSeconds <= 0) {
            throw new InvalidArgumentException('Timeout must be greater than zero.');
        }
    }
}
