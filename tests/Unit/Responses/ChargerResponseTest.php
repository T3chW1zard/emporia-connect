<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Responses;

use T3chW1zard\EmporiaConnect\Responses\ChargerResponse;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class ChargerResponseTest extends TestCase
{
    public function test_creates_from_api_response(): void
    {
        $data = [
            'deviceGid' => 222,
            'evCharger' => ['chargerOn' => true, 'status' => 'charging', 'chargingRate' => 7.2, 'maxChargingRate' => 11.5, 'offPeakSchedulesEnabled' => false],
        ];

        $response = ChargerResponse::from($data);

        $this->assertSame(222, $response->deviceGid);
        $this->assertTrue($response->chargerOn);
        $this->assertSame('charging', $response->status);
        $this->assertEqualsWithDelta(7.2, $response->chargingRate, PHP_FLOAT_EPSILON);
    }

    public function test_converts_to_array(): void
    {
        $data  = ['deviceGid' => 222, 'evCharger' => ['chargerOn' => false, 'offPeakSchedulesEnabled' => false]];
        $array = ChargerResponse::from($data)->toArray();

        $this->assertFalse($array['chargerOn']);
    }
}
