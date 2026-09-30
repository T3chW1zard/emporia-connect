<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Support;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * Type-safe extraction helpers for mixed API response data.
 * Used internally by Response classes to satisfy PHPStan level 9.
 */
final class DataExtractor
{
    /** @param array<array-key, mixed> $data */
    public static function int(array $data, string $key, int $default = 0): int
    {
        return self::nullableInt($data, $key) ?? $default;
    }

    /** @param array<array-key, mixed> $data */
    public static function string(array $data, string $key, string $default = ''): string
    {
        return self::nullableString($data, $key) ?? $default;
    }

    /** @param array<array-key, mixed> $data */
    public static function float(array $data, string $key, float $default = 0.0): float
    {
        return self::nullableFloat($data, $key) ?? $default;
    }

    /** @param array<array-key, mixed> $data */
    public static function bool(array $data, string $key, bool $default = false): bool
    {
        return self::nullableBool($data, $key) ?? $default;
    }

    /** @param array<array-key, mixed> $data */
    public static function nullableInt(array $data, string $key): ?int
    {
        $value = $data[$key] ?? null;

        return match (true) {
            is_int($value) => $value,
            is_float($value), is_bool($value) => (int) $value,
            is_string($value) && is_numeric($value) => (int) $value,
            default => null,
        };
    }

    /** @param array<array-key, mixed> $data */
    public static function nullableString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return match (true) {
            is_string($value) => $value,
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) $value,
            default => null,
        };
    }

    /** @param array<array-key, mixed> $data */
    public static function nullableFloat(array $data, string $key): ?float
    {
        $value = $data[$key] ?? null;

        return match (true) {
            is_float($value) => $value,
            is_int($value) => (float) $value,
            is_string($value) && is_numeric($value) => (float) $value,
            default => null,
        };
    }

    /** @param array<array-key, mixed> $data */
    public static function nullableBool(array $data, string $key): ?bool
    {
        $value = $data[$key] ?? null;

        return match (true) {
            is_bool($value) => $value,
            is_int($value), is_float($value) => $value != 0,
            is_string($value) => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            default => null,
        };
    }

    /**
     * Parse a date string. Invalid or missing values return null instead of throwing.
     *
     * The API reports offline times as e.g. "since Sep 29, 2026, 5:30 PM"; the "since" prefix is ignored.
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function nullableDate(array $data, string $key): ?DateTimeImmutable
    {
        $value = self::nullableString($data, $key);
        $value = $value === null ? null : trim((string) preg_replace('/^\s*since\s+/i', '', $value));

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value, new DateTimeZone('UTC'));
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    public static function array(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * Nested object, or null when missing / not an object.
     *
     * @param  array<array-key, mixed>  $data
     * @return array<string, mixed>|null
     */
    public static function object(array $data, string $key): ?array
    {
        $value = $data[$key] ?? null;

        if (! is_array($value) || $value === []) {
            return null;
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * List of objects, skipping any entry that is not an object.
     *
     * @param  array<array-key, mixed>  $data
     * @return list<array<string, mixed>>
     */
    public static function objects(array $data, ?string $key = null): array
    {
        $items = $key === null ? $data : self::array($data, $key);
        $objects = [];

        foreach ($items as $item) {
            if (is_array($item) && $item !== []) {
                /** @var array<string, mixed> $item */
                $objects[] = $item;
            }
        }

        return $objects;
    }
}
