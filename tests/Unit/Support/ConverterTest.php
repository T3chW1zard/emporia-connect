<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Support;

use InvalidArgumentException;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Support\Converter;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class ConverterTest extends TestCase
{
    public function test_converts_kwh_to_watts_for_second_scale(): void
    {
        // 1 kWh over 1 second = 1000 W * 3600 = 3,600,000 W
        $this->assertEqualsWithDelta(3600000.0, Converter::toWatts(1.0, Scale::SECOND), PHP_FLOAT_EPSILON);
    }

    public function test_converts_kwh_to_watts_for_minute_scale(): void
    {
        // 1 kWh over 1 minute = 1000 W * 60 = 60,000 W
        $this->assertEqualsWithDelta(60000.0, Converter::toWatts(1.0, Scale::MINUTE), PHP_FLOAT_EPSILON);
    }

    public function test_throws_for_unsupported_scale(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Converter::toWatts(1.0, Scale::HOUR);
    }

    public function test_converts_zero_to_zero_watts(): void
    {
        $this->assertEqualsWithDelta(0.0, Converter::toWatts(0.0, Scale::MINUTE), PHP_FLOAT_EPSILON);
    }

    public function test_to_precise_float_default_precision(): void
    {
        $result = Converter::toPreciseFloat(0.000123456789);
        $this->assertIsString($result);
        // PHP float representation loses precision beyond ~15 significant digits;
        // verify it is a string with no scientific notation and starts with the correct prefix
        $this->assertStringStartsWith('0.000123456788', $result);
    }

    public function test_to_precise_float_custom_precision(): void
    {
        $result = Converter::toPreciseFloat(1.5, 3);
        $this->assertSame('1.500', $result);
    }

    public function test_to_precise_float_avoids_scientific_notation(): void
    {
        $result = Converter::toPreciseFloat(1.23e-10, 20);
        $this->assertStringNotContainsString('e', strtolower($result));
    }
}
