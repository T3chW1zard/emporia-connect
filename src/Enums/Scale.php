<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Enums;

/**
 * Time scale for energy usage data points.
 * Values match the Emporia Vue API (ported from PyEmVue).
 */
enum Scale: string
{
    case SECOND     = '1S';
    case MINUTE     = '1MIN';
    case MINUTES_15 = '15MIN';
    case HOUR       = '1H';
    case DAY        = '1D';
    case WEEK       = '1W';
    case MONTH      = '1MON';
    case YEAR       = '1Y';
}
