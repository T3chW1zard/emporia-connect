<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Testing;

use T3chW1zard\EmporiaConnect\Responses\DeviceResponse;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Testing\FakeClient;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class FakeClientTest extends TestCase
{
    private FakeClient $client;

    protected function setUp(): void
    {
        $this->client = new FakeClient();
    }

    public function test_customers_me_returns_customer_response(): void
    {
        $customer = $this->client->customers()->me();

        $this->assertSame(1, $customer->customerGid);
        $this->assertSame('fake@example.com', $customer->email);
    }

    public function test_devices_all_returns_array_of_devices(): void
    {
        $devices = $this->client->devices()->all();

        $this->assertCount(1, $devices);
        $this->assertSame(1, $devices[0]->deviceGid);
    }

    public function test_devices_find_returns_device(): void
    {
        $device = $this->client->devices()->find(1);

        $this->assertInstanceOf(DeviceResponse::class, $device);
        $this->assertSame(1, $device->deviceGid);
    }

    public function test_channels_all_returns_channels(): void
    {
        $channels = $this->client->channels()->all(1, Scale::HOUR);

        $this->assertCount(1, $channels);
        $this->assertSame('1', $channels[0]->channelNum);
    }

    public function test_channels_usage_returns_usage_response(): void
    {
        $usage = $this->client->channels()->usage(1, '1', Scale::HOUR, Unit::KILOWATT_HOURS);

        $this->assertCount(3, $usage->usage);
    }

    public function test_outlets_all_returns_outlets(): void
    {
        $outlets = $this->client->outlets()->all();

        $this->assertCount(1, $outlets);
        $this->assertFalse($outlets[0]->outletOn);
    }

    public function test_outlets_update_returns_outlet(): void
    {
        $outlet = $this->client->outlets()->update(10, true);

        $this->assertTrue($outlet->outletOn);
    }

    public function test_chargers_all_returns_chargers(): void
    {
        $chargers = $this->client->chargers()->all();

        $this->assertCount(1, $chargers);
        $this->assertFalse($chargers[0]->chargerOn);
    }

    public function test_chargers_update_returns_charger(): void
    {
        $charger = $this->client->chargers()->update(20, true);

        $this->assertTrue($charger->chargerOn);
    }
}
