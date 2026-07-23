<?php

declare(strict_types=1);

namespace Webfleet\Connect\Internal;

use DateTimeImmutable;
use DateTimeZone;
use Throwable;
use UnexpectedValueException;
use Webfleet\Connect\Dto\GeoPoint;

/** @internal */
final class ApiValue
{
    /** @param array<string, mixed> $row */
    public static function string(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;
        if (null === $value || '' === $value) {
            return null;
        }
        if (!is_scalar($value)) {
            throw new UnexpectedValueException(sprintf('Field "%s" is not scalar.', $key));
        }

        return (string) $value;
    }

    /** @param array<string, mixed> $row */
    public static function int(array $row, string $key): ?int
    {
        $value = $row[$key] ?? null;
        if (null === $value || '' === $value) {
            return null;
        }
        if (!is_int($value) && !(is_string($value) && preg_match('/^-?\d+$/D', $value))) {
            throw new UnexpectedValueException(sprintf('Field "%s" is not an integer.', $key));
        }

        return (int) $value;
    }

    /** @param array<string, mixed> $row */
    public static function float(array $row, string $key): ?float
    {
        $value = $row[$key] ?? null;
        if (null === $value || '' === $value) {
            return null;
        }
        if (!is_numeric($value)) {
            throw new UnexpectedValueException(sprintf('Field "%s" is not numeric.', $key));
        }

        return (float) $value;
    }

    /** @param array<string, mixed> $row */
    public static function date(array $row, string $key): ?DateTimeImmutable
    {
        $value = self::string($row, $key);
        if (null === $value) {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->setTimezone(new DateTimeZone('UTC'));
        } catch (Throwable $exception) {
            throw new UnexpectedValueException(sprintf('Field "%s" is not a valid date-time.', $key), 0, $exception);
        }
    }

    /** @param array<string, mixed> $row */
    public static function point(array $row, string $latitudeKey, string $longitudeKey): ?GeoPoint
    {
        $latitude = self::int($row, $latitudeKey);
        $longitude = self::int($row, $longitudeKey);

        return null !== $latitude && null !== $longitude ? new GeoPoint($latitude, $longitude) : null;
    }
}
