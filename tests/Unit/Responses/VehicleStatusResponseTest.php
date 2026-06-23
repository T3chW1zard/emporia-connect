<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Responses;

use T3chW1zard\EmporiaConnect\Responses\VehicleStatusResponse;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class VehicleStatusResponseTest extends TestCase
{
    public function test_creates_from_api_response(): void
    {
        $data = ['vehicleGid' => 555, 'vehicleState' => 'online', 'batteryLevel' => 80.5, 'batteryRange' => 210.3, 'chargingState' => 'Charging', 'chargeLimitPercent' => 90.0, 'minutesToFullCharge' => 45];

        $response = VehicleStatusResponse::from($data);

        $this->assertSame(555, $response->vehicleGid);
        $this->assertEqualsWithDelta(80.5, $response->batteryLevel, PHP_FLOAT_EPSILON);
        $this->assertSame(45, $response->minutesToFullCharge);
    }
}
