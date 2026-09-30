<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Resources;

use T3chW1zard\EmporiaConnect\Client;
use T3chW1zard\EmporiaConnect\Responses\ChannelTypeResponse;
use T3chW1zard\EmporiaConnect\Responses\ChargerResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceChannelResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceResponse;
use T3chW1zard\EmporiaConnect\Testing\FakeTransporter;
use T3chW1zard\EmporiaConnect\Testing\Fixtures;
use T3chW1zard\EmporiaConnect\Tests\Support\FrozenClock;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class ResourcesTest extends TestCase
{
    private FakeTransporter $transporter;

    private Client $client;

    protected function setUp(): void
    {
        $this->transporter = new FakeTransporter;
        $this->client = new Client($this->transporter, clock: new FrozenClock);
    }

    public function test_customers_me(): void
    {
        $customer = $this->client->customers()->me();

        $this->assertSame(1234, $customer->customerGid);
        $this->assertSame('you@example.com', $customer->email);
        $this->assertSame('First Last', $customer->fullName());
        $this->assertTrue($this->transporter->hasSent('GET', 'customers'));
    }

    public function test_devices_all_flattens_sub_devices_like_pyemvue(): void
    {
        $devices = $this->client->devices()->all();

        $this->assertSame([2345, 2346, 3456, 4567], array_map(static fn (DeviceResponse $d): int => $d->deviceGid, $devices));
        $this->assertCount(3, $devices[0]->channels);
        $this->assertSame('Garage', $devices[1]->channels[0]->name);
        $this->assertTrue($devices[2]->isOutlet());
        $this->assertTrue($devices[3]->isCharger());
        $this->assertTrue($this->transporter->hasSent('GET', 'customers/devices'));
    }

    public function test_devices_find(): void
    {
        $this->assertSame('SSO001', $this->client->devices()->find(3456)?->model);
        $this->assertNull($this->client->devices()->find(1));
    }

    public function test_devices_location_properties(): void
    {
        $properties = $this->client->devices()->locationProperties(2345);

        $this->assertSame(2345, $properties->deviceGid);
        $this->assertSame('America/New_York', $properties->timeZone);
        $this->assertTrue($this->transporter->hasSent('GET', 'devices/2345/locationProperties'));
    }

    public function test_devices_populate_location_properties(): void
    {
        $device = new DeviceResponse(3456, 'X', 'SSO001', null);

        $populated = $this->client->devices()->populateLocationProperties($device);

        $this->assertNull($device->locationProperties);
        $this->assertSame(3456, $populated->locationProperties?->deviceGid);
    }

    public function test_devices_status_and_connection_status(): void
    {
        $status = $this->client->devices()->status();

        $this->assertCount(1, $status->outlets);
        $this->assertCount(1, $status->chargers);
        $this->assertCount(3, $status->devicesConnected);

        $devices = $this->client->devices()->withConnectionStatus($this->client->devices()->all());
        $byGid = array_column(array_map(static fn (DeviceResponse $d): array => ['gid' => $d->deviceGid, 'device' => $d], $devices), 'device', 'gid');

        $this->assertTrue($byGid[3456]->connected);
        $this->assertNull($byGid[3456]->offlineSince);
        $this->assertFalse($byGid[4567]->connected);
        $this->assertSame('2024-06-01T08:00:00+00:00', $byGid[4567]->offlineSince?->format(DATE_ATOM));
    }

    public function test_channels_all_find_and_types(): void
    {
        $channels = $this->client->channels()->all(2345);

        $this->assertCount(3, $channels);
        $this->assertSame('Kitchen', $this->client->channels()->find(2345, '1')?->name);
        $this->assertNull($this->client->channels()->find(2345, '99'));
        $this->assertSame([], $this->client->channels()->all(1));

        $types = $this->client->channels()->types();
        $this->assertContainsOnlyInstancesOf(ChannelTypeResponse::class, $types);
        $this->assertSame('Air Conditioner', $types[0]->description);
        $this->assertTrue($this->transporter->hasSent('GET', 'devices/channels/channeltypes'));
    }

    public function test_channels_update_puts_channel_payload(): void
    {
        $channel = $this->client->channels()->find(2345, '1');
        $this->assertInstanceOf(DeviceChannelResponse::class, $channel);

        $updated = $this->client->channels()->update($channel->withName('Oven')->withChannelMultiplier(2.0)->withChannelTypeGid(5));

        $this->assertSame('Oven', $updated->name);
        $request = $this->transporter->sentTo('PUT', 'devices/2345/channels')[0];
        $this->assertSame([
            'deviceGid' => 2345,
            'name' => 'Oven',
            'channelNum' => '1',
            'channelMultiplier' => 2.0,
            'channelTypeGid' => 5,
            'type' => 'FiftyAmp',
            'parentChannelNum' => null,
        ], $request['payload']);
    }

    public function test_outlets_all_and_update(): void
    {
        $outlet = $this->client->outlets()->all()[0];
        $this->assertFalse($outlet->outletOn);
        $this->assertSame(5678, $outlet->loadGid);

        $updated = $this->client->outlets()->turnOn($outlet);

        $this->assertTrue($updated->outletOn);
        $this->assertSame(['deviceGid' => 3456, 'outletOn' => true, 'loadGid' => 5678], $this->transporter->sentTo('PUT', 'devices/outlet')[0]['payload']);

        $this->assertFalse($this->client->outlets()->turnOff($updated)->outletOn);
        $this->assertSame(3456, $this->client->outlets()->find(3456)?->deviceGid);
        $this->assertNull($this->client->outlets()->find(1));
    }

    public function test_outlet_update_without_state_change_keeps_state(): void
    {
        $outlet = $this->client->outlets()->all()[0];

        $this->client->outlets()->update($outlet);

        $this->assertFalse($this->transporter->sentTo('PUT', 'devices/outlet')[0]['payload']['outletOn']);
    }

    public function test_chargers_all_and_update(): void
    {
        $charger = $this->client->chargers()->all()[0];
        $this->assertTrue($charger->chargerOn);
        $this->assertSame(25, $charger->chargingRate);

        $updated = $this->client->chargers()->update($charger, on: false, chargeRate: 32);

        $this->assertFalse($updated->chargerOn);
        $this->assertSame(32, $updated->chargingRate);
        $this->assertSame([
            'deviceGid' => 4567,
            'loadGid' => 6789,
            'chargerOn' => false,
            'chargingRate' => 32,
            'maxChargingRate' => 40,
        ], $this->transporter->sentTo('PUT', 'devices/evcharger')[0]['payload']);
    }

    public function test_charger_rate_of_zero_is_ignored_like_pyemvue(): void
    {
        $charger = $this->client->chargers()->find(4567);
        $this->assertInstanceOf(ChargerResponse::class, $charger);

        $this->client->chargers()->update($charger, chargeRate: 0);

        $this->assertSame(25, $this->transporter->sentTo('PUT', 'devices/evcharger')[0]['payload']['chargingRate']);
        $this->assertTrue($this->client->chargers()->turnOn($charger)->chargerOn);
        $this->assertFalse($this->client->chargers()->turnOff($charger)->chargerOn);
    }

    public function test_vehicles(): void
    {
        $vehicles = $this->client->vehicles()->all();
        $this->assertSame('Tesla', $vehicles[0]->make);

        $status = $this->client->vehicles()->status(789);
        $this->assertSame(80.0, $status?->batteryLevel);
        $this->assertTrue($this->transporter->hasSent('GET', 'vehicles/v2/settings?vehicleGid=789'));
    }

    public function test_vehicle_status_is_null_for_empty_response(): void
    {
        $this->transporter->respondWith('GET vehicles/v2/settings*', []);

        $this->assertNull($this->client->vehicles()->status(1));
    }

    public function test_updates_fall_back_to_the_request_when_the_response_is_empty(): void
    {
        $this->transporter->respondWith('PUT devices/outlet', [])->respondWith('PUT devices/evcharger', [])->respondWith('PUT devices/*/channels', []);

        $outlet = $this->client->outlets()->all()[0];
        $charger = $this->client->chargers()->all()[0];
        $channel = $this->client->channels()->all(2345)[0];

        $this->assertTrue($this->client->outlets()->turnOn($outlet)->outletOn);
        $this->assertFalse($this->client->chargers()->turnOff($charger)->chargerOn);
        $this->assertSame($channel, $this->client->channels()->update($channel));
    }

    public function test_maintenance_is_null_without_checker(): void
    {
        $this->assertNull($this->client->downForMaintenance());
    }

    public function test_fixture_shapes_are_exposed(): void
    {
        $this->assertArrayHasKey('devices', Fixtures::devices());
    }
}
