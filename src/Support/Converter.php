<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Support;

use InvalidArgumentException;
use T3chW1zard\EmporiaConnect\Enums\Scale;

/**
 * Unit conversion helpers.
 *
 * The API returns energy (kWh) consumed during each data point. PyEmVue leaves the
 * conversion to the caller ("1MIN in kW = 60 * result"); these helpers do it for you.
 */
final class Converter
{
    /**
     * Convert the kWh used during one data point into the average power in watts.
     *
     * @throws InvalidArgumentException for calendar based scales (month, year) that have no fixed length
     */
    public static function kilowattHoursToWatts(float $kilowattHours, Scale $scale): float
    {
        $seconds = $scale->seconds();

        if ($seconds === null) {
            throw new InvalidArgumentException(
                "Cannot convert to watts for scale '{$scale->value}' because it has no fixed length.",
            );
        }

        return $kilowattHours * 1000 * 3600 / $seconds;
    }
}
