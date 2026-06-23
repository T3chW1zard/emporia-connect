<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Enums;

use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class ScaleTest extends TestCase
{
    public function test_all_cases_have_correct_api_values(): void
    {
        $this->assertSame('1S', Scale::SECOND->value);
        $this->assertSame('1MIN', Scale::MINUTE->value);
        $this->assertSame('15MIN', Scale::MINUTES_15->value);
        $this->assertSame('1H', Scale::HOUR->value);
        $this->assertSame('1D', Scale::DAY->value);
        $this->assertSame('1W', Scale::WEEK->value);
        $this->assertSame('1MON', Scale::MONTH->value);
        $this->assertSame('1Y', Scale::YEAR->value);
    }

    public function test_can_create_from_string(): void
    {
        $this->assertSame(Scale::MINUTE, Scale::from('1MIN'));
        $this->assertSame(Scale::HOUR, Scale::from('1H'));
    }

    public function test_invalid_value_returns_null(): void
    {
        $this->assertNull(Scale::tryFrom('INVALID'));
    }
}
