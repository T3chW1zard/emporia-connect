<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Support;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Support\Converter;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;
use T3chW1zard\EmporiaConnect\Support\SystemClock;
use T3chW1zard\EmporiaConnect\Support\Time;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class SupportTest extends TestCase
{
    /** @return iterable<string, array{Scale, float}> */
    public static function wattConversions(): iterable
    {
        yield 'second' => [Scale::SECOND, 3_600_000.0];
        yield 'minute' => [Scale::MINUTE, 60_000.0];
        yield '15 minutes' => [Scale::MINUTES_15, 4_000.0];
        yield 'hour' => [Scale::HOUR, 1_000.0];
        yield 'day' => [Scale::DAY, 1_000 / 24];
        yield 'week' => [Scale::WEEK, 1_000 / 168];
    }

    #[DataProvider('wattConversions')]
    public function test_kilowatt_hours_to_watts(Scale $scale, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, Converter::kilowattHoursToWatts(1.0, $scale), 0.0001);
    }

    public function test_watts_conversion_rejects_calendar_scales(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Converter::kilowattHoursToWatts(1.0, Scale::YEAR);
    }

    public function test_scale_helpers(): void
    {
        $this->assertSame(900, Scale::MINUTES_15->seconds());
        $this->assertNull(Scale::MONTH->seconds());
        $this->assertSame('+1 month', Scale::MONTH->modifier());
        $this->assertSame('-12 hours', Scale::MINUTE->defaultWindow());

        foreach (Scale::cases() as $scale) {
            $this->assertNotFalse((new DateTimeImmutable)->modify($scale->modifier()));
            $this->assertNotFalse((new DateTimeImmutable)->modify($scale->defaultWindow()));
        }
    }

    public function test_time_format_converts_to_utc_with_milliseconds(): void
    {
        $this->assertSame('2024-01-02T02:04:05.000Z', Time::format(new DateTimeImmutable('2024-01-02 03:04:05', new DateTimeZone('Europe/Amsterdam'))));
        $this->assertSame('2024-01-02T03:04:05.000Z', Time::format('2024-01-02 03:04:05'));
        $this->assertSame('UTC', Time::toUtc('2024-01-01')->getTimezone()->getName());
    }

    public function test_time_rejects_invalid_strings(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Time::toUtc('not a date');
    }

    public function test_system_clock_is_utc(): void
    {
        $this->assertSame('UTC', (new SystemClock)->now()->getTimezone()->getName());
    }

    public function test_data_extractor_scalars(): void
    {
        $data = ['int' => '5', 'float' => '1.5', 'bool' => 'true', 'no' => 0, 'string' => 12, 'flag' => false, 'array' => ['x'], 'null' => null];

        $this->assertSame(5, DataExtractor::int($data, 'int'));
        $this->assertSame(7, DataExtractor::int($data, 'missing', 7));
        $this->assertNull(DataExtractor::nullableInt($data, 'array'));
        $this->assertSame(1.5, DataExtractor::float($data, 'float'));
        $this->assertNull(DataExtractor::nullableFloat($data, 'string-not-there'));
        $this->assertTrue(DataExtractor::bool($data, 'bool'));
        $this->assertFalse(DataExtractor::bool($data, 'no', true));
        $this->assertNull(DataExtractor::nullableBool($data, 'null'));
        $this->assertSame('12', DataExtractor::string($data, 'string'));
        $this->assertSame('false', DataExtractor::string($data, 'flag'));
        $this->assertNull(DataExtractor::nullableString($data, 'array'));
    }

    public function test_data_extractor_structures(): void
    {
        $data = ['obj' => ['a' => 1], 'empty' => [], 'list' => [['a' => 1], 'junk', [], ['b' => 2]], 'date' => '2024-01-01T00:00:00Z', 'bad' => 'nope'];

        $this->assertSame(['a' => 1], DataExtractor::object($data, 'obj'));
        $this->assertNull(DataExtractor::object($data, 'empty'));
        $this->assertSame([['a' => 1], ['b' => 2]], DataExtractor::objects($data, 'list'));
        $this->assertSame([['a' => 1]], DataExtractor::objects([['a' => 1], 5]));
        $this->assertSame([], DataExtractor::array($data, 'date'));
        $this->assertSame('2024-01-01', DataExtractor::nullableDate($data, 'date')?->format('Y-m-d'));
        $this->assertNull(DataExtractor::nullableDate($data, 'bad'));
        $this->assertNull(DataExtractor::nullableDate($data, 'missing'));
    }
}
