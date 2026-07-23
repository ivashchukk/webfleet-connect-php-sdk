<?php

declare(strict_types=1);

namespace Webfleet\Connect;

use InvalidArgumentException;
use SensitiveParameter;

final readonly class Credentials
{
    public function __construct(
        private string $account,
        private string $username,
        #[SensitiveParameter]
        private string $password,
        #[SensitiveParameter]
        private string $apiKey,
    ) {
        foreach (['account' => $account, 'username' => $username, 'password' => $password, 'apiKey' => $apiKey] as $name => $value) {
            if ('' === trim($value)) {
                throw new InvalidArgumentException(sprintf('Credential "%s" must not be empty.', $name));
            }
        }
    }

    public static function fromEnvironment(): self
    {
        return new self(
            self::environment('WEBFLEET_CONNECT_ACCOUNT'),
            self::environment('WEBFLEET_CONNECT_USERNAME'),
            self::environment('WEBFLEET_CONNECT_PASSWORD'),
            self::environment('WEBFLEET_CONNECT_APIKEY'),
        );
    }

    public function account(): string
    {
        return $this->account;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function basicAuthorization(): string
    {
        return 'Basic ' . base64_encode($this->username . ':' . $this->password);
    }

    /** @return array{account: string, username: string, password: string, apiKey: string} */
    public function __debugInfo(): array
    {
        return [
            'account' => $this->account,
            'username' => $this->username,
            'password' => '[redacted]',
            'apiKey' => '[redacted]',
        ];
    }

    private static function environment(string $name): string
    {
        $value = getenv($name);

        if (false === $value || '' === trim($value)) {
            throw new InvalidArgumentException(sprintf('Required environment variable "%s" is not set.', $name));
        }

        return $value;
    }
}
