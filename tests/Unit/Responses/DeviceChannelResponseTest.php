<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Responses;

use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Responses\DeviceChannelResponse;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class DeviceChannelResponseTest extends TestCase
{
    public function test_creates_from_api_response_for_hour_scale(): void
    {
        $data = ['deviceGid' => 1, 'channelNum' => '1', 'name' => 'Main', 'usage' => 0.5, 'percentage' => null, 'channelTypeGid' => null];

        $response = DeviceChannelResponse::from($data, Unit::KILOWATT_HOURS, Scale::HOUR);

        $this->assertSame(1, $response->deviceGid);
        $this->assertSame('1', $response->channelNum);
        $this->assertSame('Main', $response->name);
        $this->assertSame(Scale::HOUR, $response->scale);
    }

    public function test_converts_to_watts_for_minute_scale(): void
    {
        $data = ['deviceGid' => 1, 'channelNum' => '1', 'name' => 'Main', 'usage' => 0.001, 'percentage' => null, 'channelTypeGid' => null];

        $response = DeviceChannelResponse::from($data, Unit::KILOWATT_HOURS, Scale::MINUTE);

        // 0.001 kWh over 1 minute = 60 W
        $this->assertEqualsWithDelta(60.0, $response->currentUsage, PHP_FLOAT_EPSILON);
    }

    public function test_converts_to_array(): void
    {
        $data = ['deviceGid' => 1, 'channelNum' => '1', 'name' => 'Main', 'usage' => 0.5, 'percentage' => 100.0, 'channelTypeGid' => 1];
        $array = DeviceChannelResponse::from($data, Unit::KILOWATT_HOURS, Scale::HOUR)->toArray();

        $this->assertArrayHasKey('deviceGid', $array);
        $this->assertArrayHasKey('currentUsage', $array);
        $this->assertArrayHasKey('unit', $array);
    }
}
