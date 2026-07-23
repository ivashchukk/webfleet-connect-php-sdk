<?php

declare(strict_types=1);

namespace Webfleet\Connect\Value;

use InvalidArgumentException;

final readonly class ObjectIdentifier
{
    private function __construct(public ?string $number, public ?string $uid) {}

    public static function number(string $number): self
    {
        self::notBlank($number, 'Object number');

        return new self($number, null);
    }

    public static function uid(string $uid): self
    {
        self::notBlank($uid, 'Object UID');

        return new self(null, $uid);
    }

    /** @return array{objectno: string}|array{objectuid: string} */
    public function toParameters(): array
    {
        return null !== $this->number ? ['objectno' => $this->number] : ['objectuid' => $this->uid ?? ''];
    }

    private static function notBlank(string $value, string $label): void
    {
        if ('' === trim($value)) {
            throw new InvalidArgumentException(sprintf('%s must not be empty.', $label));
        }
    }
}
