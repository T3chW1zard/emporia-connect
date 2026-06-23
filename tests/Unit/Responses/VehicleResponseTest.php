<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Responses;

use T3chW1zard\EmporiaConnect\Responses\VehicleResponse;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class VehicleResponseTest extends TestCase
{
    public function test_creates_from_api_response(): void
    {
        $data = ['vehicleGid' => 555, 'vendor' => 'tesla', 'displayName' => 'My Tesla', 'make' => 'Tesla', 'model' => 'Model 3', 'year' => 2022];

        $response = VehicleResponse::from($data);

        $this->assertSame(555, $response->vehicleGid);
        $this->assertSame('tesla', $response->vendor);
        $this->assertSame(2022, $response->year);
    }
}
