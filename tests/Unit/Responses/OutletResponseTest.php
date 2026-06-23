<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Responses;

use T3chW1zard\EmporiaConnect\Responses\OutletResponse;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class OutletResponseTest extends TestCase
{
    public function test_creates_from_api_response(): void
    {
        $data = ['deviceGid' => 111, 'outlet' => ['outletOn' => true, 'loadGid' => 42, 'schedules' => []]];
        $response = OutletResponse::from($data);

        $this->assertSame(111, $response->deviceGid);
        $this->assertTrue($response->outletOn);
        $this->assertSame(42, $response->loadGid);
    }

    public function test_converts_to_array(): void
    {
        $data  = ['deviceGid' => 111, 'outlet' => ['outletOn' => false, 'loadGid' => null, 'schedules' => []]];
        $array = OutletResponse::from($data)->toArray();

        $this->assertFalse($array['outletOn']);
        $this->assertNull($array['loadGid']);
    }
}
