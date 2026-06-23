<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Responses;

use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Responses\ChannelUsageResponse;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class ChannelUsageResponseTest extends TestCase
{
    public function test_creates_with_timestamps(): void
    {
        $data = ['firstUsageInstant' => '2024-06-01T00:00:00Z', 'usageList' => [0.001, 0.002, 0.003]];
        $response = ChannelUsageResponse::from($data, '1', Unit::KILOWATT_HOURS, Scale::HOUR);

        $this->assertSame('1', $response->channelNum);
        $this->assertCount(3, $response->usage);
        $this->assertArrayHasKey('time', $response->usage[0]);
        $this->assertArrayHasKey('usage', $response->usage[0]);
    }

    public function test_creates_without_timestamps(): void
    {
        $data = ['firstUsageInstant' => '2024-06-01T00:00:00Z', 'usageList' => [0.001, 0.002]];
        $response = ChannelUsageResponse::from($data, '1', Unit::KILOWATT_HOURS, Scale::HOUR, false);

        $this->assertIsFloat($response->usage[0]);
    }

    public function test_converts_to_watts_for_minute_scale(): void
    {
        $data = ['firstUsageInstant' => '2024-06-01T00:00:00Z', 'usageList' => [0.001]];
        $response = ChannelUsageResponse::from($data, '1', Unit::KILOWATT_HOURS, Scale::MINUTE);

        // 0.001 kWh over 1 minute = 0.001 * 1000 / (60/3600) = 60 W
        $this->assertEqualsWithDelta(60.0, $response->usage[0]['usage'], PHP_FLOAT_EPSILON);
    }

    public function test_converts_to_array(): void
    {
        $data = ['firstUsageInstant' => '2024-06-01T00:00:00Z', 'usageList' => []];
        $array = ChannelUsageResponse::from($data, '1', Unit::KILOWATT_HOURS, Scale::HOUR)->toArray();

        $this->assertArrayHasKey('channelNum', $array);
        $this->assertArrayHasKey('unit', $array);
        $this->assertArrayHasKey('scale', $array);
    }
}
