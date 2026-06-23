<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Support;

/**
 * Type-safe extraction helpers for mixed API response data.
 * Used internally by Response classes to satisfy PHPStan level 9.
 */
final class DataExtractor
{
    /** @param array<string, mixed> $data */
    public static function int(array $data, string $key, int $default = 0): int
    {
        $value = $data[$key] ?? $default;

        return is_int($value) ? $value : (int) (is_scalar($value) ? $value : $default);
    }

    /** @param array<string, mixed> $data */
    public static function string(array $data, string $key, string $default = ''): string
    {
        $value = $data[$key] ?? $default;

        return is_string($value) ? $value : (string) (is_scalar($value) ? $value : $default);
    }

    /** @param array<string, mixed> $data */
    public static function float(array $data, string $key, float $default = 0.0): float
    {
        $value = $data[$key] ?? $default;

        return is_float($value) ? $value : (float) (is_scalar($value) ? $value : $default);
    }

    /** @param array<string, mixed> $data */
    public static function bool(array $data, string $key, bool $default = false): bool
    {
        $value = $data[$key] ?? $default;

        return is_bool($value) ? $value : (bool) (is_scalar($value) ? $value : $default);
    }

    /** @param array<string, mixed> $data */
    public static function nullableInt(array $data, string $key): ?int
    {
        if (! isset($data[$key])) {
            return null;
        }
        $value = $data[$key];

        return is_int($value) ? $value : (int) (is_scalar($value) ? $value : 0);
    }

    /** @param array<string, mixed> $data */
    public static function nullableString(array $data, string $key): ?string
    {
        if (! isset($data[$key])) {
            return null;
        }
        $value = $data[$key];

        return is_string($value) ? $value : (string) (is_scalar($value) ? $value : '');
    }

    /** @param array<string, mixed> $data */
    public static function nullableFloat(array $data, string $key): ?float
    {
        if (! isset($data[$key])) {
            return null;
        }
        $value = $data[$key];

        return is_float($value) ? $value : (float) (is_scalar($value) ? $value : 0.0);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function array(array $data, string $key): array
    {
        $value = $data[$key] ?? null;

        return is_array($value) ? $value : [];
    }
}
