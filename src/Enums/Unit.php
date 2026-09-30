<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Enums;

/**
 * Energy unit accepted by the Emporia Vue API.
 */
enum Unit: string
{
    case VOLTS = 'Voltage';
    case KILOWATT_HOURS = 'KilowattHours';
    case DOLLARS = 'Dollars';
    case AMP_HOURS = 'AmpHours';
    case TREES = 'Trees';
    case GALLONS_OF_GAS = 'GallonsOfGas';
    case MILES_DRIVEN = 'MilesDriven';
    case CARBON = 'Carbon';
}
