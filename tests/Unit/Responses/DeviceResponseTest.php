<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Responses;

use DateTimeImmutable;
use T3chW1zard\EmporiaConnect\Responses\DeviceResponse;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class DeviceResponseTest extends TestCase
{
    private array $fixture = [
        'deviceGid'            => 9999,
        'manufacturerDeviceId' => 'ABC123',
        'model'                => 'Vue002',
        'firmware'             => '1.7.4',
        'parentDeviceGid'      => null,
        'parentChannelNum'     => null,
        'deviceConnected'      => ['connected' => true, 'offlineSince' => null],
        'devices'              => [
            ['channels' => [['channelNum' => '1'], ['channelNum' => '2']]],
        ],
    ];

    public function test_creates_from_api_response(): void
    {
        $response = DeviceResponse::from($this->fixture);

        $this->assertSame(9999, $response->deviceGid);
        $this->assertSame('Vue002', $response->model);
        $this->assertTrue($response->isConnected);
        $this->assertNotInstanceOf(DateTimeImmutable::class, $response->offlineSince);
        $this->assertSame(['1', '2'], $response->channels);
    }

    public function test_parses_offline_since(): void
    {
        $data = $this->fixture;
        $data['deviceConnected'] = [
            'connected'    => false,
            'offlineSince' => 'since 2024-06-01T12:00:00Z',
        ];

        $response = DeviceResponse::from($data);

        $this->assertFalse($response->isConnected);
        $this->assertInstanceOf(DateTimeImmutable::class, $response->offlineSince);
        $this->assertSame('2024-06-01', $response->offlineSince->format('Y-m-d'));
    }

    public function test_converts_to_array(): void
    {
        $array = DeviceResponse::from($this->fixture)->toArray();

        $this->assertArrayHasKey('deviceGid', $array);
        $this->assertArrayHasKey('channels', $array);
        $this->assertSame(['1', '2'], $array['channels']);
    }
}
