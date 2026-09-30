<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Support;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use InvalidArgumentException;

/**
 * Date helpers for building API requests.
 */
final class Time
{
    /**
     * Normalise a user supplied date to an immutable UTC date.
     *
     * Strings without a time zone are interpreted as UTC.
     */
    public static function toUtc(DateTimeInterface|string $value): DateTimeImmutable
    {
        if (is_string($value)) {
            try {
                $value = new DateTimeImmutable($value, new DateTimeZone('UTC'));
            } catch (Exception $e) {
                throw new InvalidArgumentException("Invalid date '{$value}': ".$e->getMessage(), 0, $e);
            }
        }

        return DateTimeImmutable::createFromInterface($value)->setTimezone(new DateTimeZone('UTC'));
    }

    /**
     * Format a date the way the Emporia API expects it, e.g. 2024-01-01T00:00:00.000Z.
     */
    public static function format(DateTimeInterface|string $value): string
    {
        return self::toUtc($value)->format('Y-m-d\TH:i:s.v\Z');
    }
}
