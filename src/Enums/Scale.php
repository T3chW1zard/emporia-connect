<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Enums;

/**
 * Time scale for energy usage data points.
 * Values match the Emporia Vue API.
 */
enum Scale: string
{
    case SECOND = '1S';
    case MINUTE = '1MIN';
    case MINUTES_15 = '15MIN';
    case HOUR = '1H';
    case DAY = '1D';
    case WEEK = '1W';
    case MONTH = '1MON';
    case YEAR = '1Y';

    /**
     * Fixed length of one data point in seconds, or null for calendar based scales (month, year).
     */
    public function seconds(): ?int
    {
        return match ($this) {
            self::SECOND => 1,
            self::MINUTE => 60,
            self::MINUTES_15 => 900,
            self::HOUR => 3600,
            self::DAY => 86400,
            self::WEEK => 604800,
            self::MONTH, self::YEAR => null,
        };
    }

    /**
     * Relative date modifier that advances one data point.
     */
    public function modifier(): string
    {
        return match ($this) {
            self::SECOND => '+1 second',
            self::MINUTE => '+1 minute',
            self::MINUTES_15 => '+15 minutes',
            self::HOUR => '+1 hour',
            self::DAY => '+1 day',
            self::WEEK => '+1 week',
            self::MONTH => '+1 month',
            self::YEAR => '+1 year',
        };
    }

    /**
     * Sensible look-back window used when no chart start date is supplied.
     */
    public function defaultWindow(): string
    {
        return match ($this) {
            self::SECOND => '-1 hour',
            self::MINUTE => '-12 hours',
            self::MINUTES_15, self::HOUR => '-1 day',
            self::DAY => '-1 week',
            self::WEEK => '-1 month',
            self::MONTH => '-1 year',
            self::YEAR => '-5 years',
        };
    }
}
