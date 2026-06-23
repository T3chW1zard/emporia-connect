<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Enums;

use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class UnitTest extends TestCase
{
    public function test_all_cases_have_correct_api_values(): void
    {
        $this->assertSame('KilowattHours', Unit::KILOWATT_HOURS->value);
        $this->assertSame('Dollars', Unit::DOLLARS->value);
        $this->assertSame('AmpHours', Unit::AMP_HOURS->value);
        $this->assertSame('Trees', Unit::TREES->value);
        $this->assertSame('GallonsOfGas', Unit::GALLONS_OF_GAS->value);
        $this->assertSame('MilesDriven', Unit::MILES_DRIVEN->value);
        $this->assertSame('Carbon', Unit::CARBON->value);
        $this->assertSame('Voltage', Unit::VOLTAGE->value);
    }
}
