<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Resources\Devices;
use T3chW1zard\EmporiaConnect\Responses\DeviceResponse;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class DevicesTest extends TestCase
{
    private function makeTransporter(array $getReturn): TransporterContract
    {
        return new class($getReturn) implements TransporterContract
        {
            public function __construct(private readonly array $getReturn) {}

            public function get(string $uri): array
            {
                return $this->getReturn;
            }

            public function put(string $uri, array $payload): array
            {
                return [];
            }
        };
    }

    private function deviceFixture(int $gid): array
    {
        return [
            'deviceGid' => $gid, 'manufacturerDeviceId' => 'X', 'model' => 'Vue002',
            'firmware' => '1.7', 'parentDeviceGid' => null, 'parentChannelNum' => null,
            'deviceConnected' => ['connected' => true, 'offlineSince' => null],
            'devices' => [],
        ];
    }

    public function test_all_returns_device_array(): void
    {
        $devices = (new Devices($this->makeTransporter(['devices' => [$this->deviceFixture(1), $this->deviceFixture(2)]])))
            ->all();

        $this->assertCount(2, $devices);
        $this->assertSame(1, $devices[0]->deviceGid);
    }

    public function test_find_returns_matching_device(): void
    {
        $device = (new Devices($this->makeTransporter(['devices' => [$this->deviceFixture(42)]])))
            ->find(42);

        $this->assertInstanceOf(DeviceResponse::class, $device);
        $this->assertSame(42, $device->deviceGid);
    }

    public function test_find_returns_null_when_not_found(): void
    {
        $this->assertNotInstanceOf(DeviceResponse::class, (new Devices($this->makeTransporter(['devices' => [$this->deviceFixture(1)]])))
            ->find(999));
    }
}
