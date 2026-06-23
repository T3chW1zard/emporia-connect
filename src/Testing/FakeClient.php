<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Testing;

use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Resources\Channels;
use T3chW1zard\EmporiaConnect\Resources\Chargers;
use T3chW1zard\EmporiaConnect\Resources\Customers;
use T3chW1zard\EmporiaConnect\Resources\Devices;
use T3chW1zard\EmporiaConnect\Resources\Outlets;

/**
 * In-memory fake client for use in consumer tests.
 * Returns predictable fixture data matching the real API shapes.
 *
 * Usage in consumer tests:
 *   $client = new FakeClient();
 *   $customer = $client->customers()->me();
 */
final class FakeClient implements ClientContract, TransporterContract
{
    public function get(string $uri): array
    {
        return match (true) {
            str_starts_with($uri, 'customers/devices/status')             => $this->deviceStatusFixture(),
            str_starts_with($uri, 'customers/devices')                    => $this->devicesFixture(),
            str_starts_with($uri, 'customers/vehicles')                   => ['vehicles' => []],
            str_starts_with($uri, 'customers')                            => $this->customerFixture(),
            str_starts_with($uri, 'AppAPI?apiMethod=getDeviceListUsages') => $this->channelListFixture(),
            str_starts_with($uri, 'AppAPI?apiMethod=getChartUsage')       => $this->chartUsageFixture(),
            str_starts_with($uri, 'devices/channels/channeltypes')        => ['channelTypes' => []],
            default                                                        => [],
        };
    }

    public function put(string $uri, array $payload): array
    {
        return match (true) {
            str_starts_with($uri, 'devices/outlet')    => $this->outletFixture(true),
            str_starts_with($uri, 'devices/evcharger') => $this->chargerFixture(true),
            default                                     => [],
        };
    }

    public function customers(): Customers
    {
        return new Customers($this);
    }

    public function devices(): Devices
    {
        return new Devices($this);
    }

    public function channels(): Channels
    {
        return new Channels($this);
    }

    public function outlets(): Outlets
    {
        return new Outlets($this);
    }

    public function chargers(): Chargers
    {
        return new Chargers($this);
    }

    /** @return array<string, mixed> */
    private function customerFixture(): array
    {
        return ['customerGid' => 1, 'email' => 'fake@example.com', 'firstName' => 'Fake', 'lastName' => 'User', 'createdAt' => '2024-01-01T00:00:00Z'];
    }

    /** @return array<string, mixed> */
    private function devicesFixture(): array
    {
        return ['devices' => [[
            'deviceGid' => 1, 'manufacturerDeviceId' => 'FAKE001', 'model' => 'Vue002',
            'firmware' => '1.7', 'parentDeviceGid' => null, 'parentChannelNum' => null,
            'deviceConnected' => ['connected' => true, 'offlineSince' => null],
            'devices' => [['channels' => [['channelNum' => '1'], ['channelNum' => '2']]]],
        ]]];
    }

    /** @return array<string, mixed> */
    private function deviceStatusFixture(): array
    {
        return ['outlets' => [$this->outletFixture(false)], 'evChargers' => [$this->chargerFixture(false)]];
    }

    /** @return array<string, mixed> */
    private function outletFixture(bool $on): array
    {
        return ['deviceGid' => 10, 'outlet' => ['outletOn' => $on, 'loadGid' => null, 'schedules' => []]];
    }

    /** @return array<string, mixed> */
    private function chargerFixture(bool $on): array
    {
        return ['deviceGid' => 20, 'evCharger' => ['chargerOn' => $on, 'offPeakSchedulesEnabled' => false]];
    }

    /** @return array<string, mixed> */
    private function channelListFixture(): array
    {
        return ['deviceListUsages' => ['devices' => [['channelUsages' => [
            ['deviceGid' => 1, 'channelNum' => '1', 'name' => 'Main', 'usage' => 0.001, 'percentage' => 100.0, 'channelTypeGid' => null],
        ]]]]];
    }

    /** @return array<string, mixed> */
    private function chartUsageFixture(): array
    {
        return ['firstUsageInstant' => '2024-06-01T00:00:00Z', 'usageList' => [0.001, 0.002, 0.003]];
    }
}
