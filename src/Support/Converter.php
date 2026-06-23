<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Support;

use InvalidArgumentException;
use T3chW1zard\EmporiaConnect\Enums\Scale;

/**
 * Static utility methods for unit conversion.
 * Replaces global helper functions from the original app.
 */
final class Converter
{
    /**
     * Convert kilowatt-hours to watts for a given time scale.
     * Only valid for SECOND and MINUTE scales.
     */
    public static function toWatts(float $kilowattHours, Scale $scale): float
    {
        $secondsInPeriod = match ($scale) {
            Scale::SECOND => 1,
            Scale::MINUTE => 60,
            default => throw new InvalidArgumentException(
                "Cannot convert to watts for scale '{$scale->value}'. Only SECOND and MINUTE are supported.",
            ),
        };

        $hoursInPeriod = $secondsInPeriod / 3600;

        return round($kilowattHours * 1000 / $hoursInPeriod);
    }

    /**
     * Format a float with high precision, avoiding scientific notation.
     */
    public static function toPreciseFloat(float $value, int $precision = 20): string
    {
        $valueAsString = sprintf('%.40f', $value);

        return bcadd($valueAsString, '0', $precision);
    }
}
