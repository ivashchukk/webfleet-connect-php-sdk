<?php

declare(strict_types=1);

namespace Webfleet\Connect\Value;

use InvalidArgumentException;

final readonly class DriverIdentifier
{
    private function __construct(public ?string $number, public ?string $uid) {}

    public static function number(string $number): self
    {
        self::notBlank($number, 'Driver number');

        return new self($number, null);
    }

    public static function uid(string $uid): self
    {
        self::notBlank($uid, 'Driver UID');

        return new self(null, $uid);
    }

    /** @return array{driverno: string}|array{driveruid: string} */
    public function toParameters(): array
    {
        return null !== $this->number ? ['driverno' => $this->number] : ['driveruid' => $this->uid ?? ''];
    }

    private static function notBlank(string $value, string $label): void
    {
        if ('' === trim($value)) {
            throw new InvalidArgumentException(sprintf('%s must not be empty.', $label));
        }
    }
}
