<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Responses;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Responses\ChannelTypeResponse;
use T3chW1zard\EmporiaConnect\Responses\ChargerResponse;
use T3chW1zard\EmporiaConnect\Responses\ChartUsageResponse;
use T3chW1zard\EmporiaConnect\Responses\CustomerResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceChannelResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceChannelUsageResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceConnectionResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceStatusResponse;
use T3chW1zard\EmporiaConnect\Responses\LocationPropertiesResponse;
use T3chW1zard\EmporiaConnect\Responses\OutletResponse;
use T3chW1zard\EmporiaConnect\Responses\UsageDeviceResponse;
use T3chW1zard\EmporiaConnect\Responses\VehicleResponse;
use T3chW1zard\EmporiaConnect\Responses\VehicleStatusResponse;
use T3chW1zard\EmporiaConnect\Testing\Fixtures;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class ResponsesTest extends TestCase
{
    public function test_customer(): void
    {
        $customer = CustomerResponse::from(Fixtures::customer());

        $this->assertSame('2020-01-01T12:34:56+00:00', $customer->createdAt?->format(DATE_ATOM));
        $this->assertSame('First', $customer->toArray()['firstName']);
        $this->assertNull(CustomerResponse::from(['createdAt' => 'garbage'])->createdAt);
    }

    public function test_device_maps_every_field(): void
    {
        $device = DeviceResponse::from(Fixtures::devices()['devices'][1]);

        $this->assertSame(3456, $device->deviceGid);
        $this->assertSame('B3207C05D9E8F2A1', $device->manufacturerDeviceId);
        $this->assertSame('Outlet-1594685591', $device->firmware);
        $this->assertSame(2345, $device->parentDeviceGid);
        $this->assertSame('1,2,3', $device->parentChannelNum);
        $this->assertFalse($device->connected);
        $this->assertSame('2024-05-01T10:00:00+00:00', $device->offlineSince?->format(DATE_ATOM));
        $this->assertSame(23, $device->channels[0]->channelTypeGid);
        $this->assertSame(5678, $device->outlet?->loadGid);
        $this->assertNull($device->evCharger);
        $this->assertSame('Plug', $device->name());
        $this->assertSame('1,2,3', $device->channel('1,2,3')?->channelNum);
        $this->assertNull($device->channel('nope'));
    }

    public function test_offline_since_with_since_prefix_is_parsed(): void
    {
        // Exact format returned by the live API for an offline Vue monitor.
        $offline = ['deviceGid' => 243600, 'connected' => false, 'offlineSince' => 'since Sep 29, 2026, 5:30 PM'];

        $this->assertSame('2026-09-29T17:30:00+00:00', DeviceConnectionResponse::from($offline)->offlineSince?->format(DATE_ATOM));

        $device = DeviceResponse::from(['deviceGid' => 243600, 'model' => 'VUE002', 'deviceConnected' => $offline]);
        $this->assertFalse($device->connected);
        $this->assertSame('2026-09-29T17:30:00+00:00', $device->offlineSince?->format(DATE_ATOM));

        $status = DeviceStatusResponse::from(['devicesConnected' => [$offline]]);
        $this->assertSame('2026-09-29T17:30:00+00:00', $status->connectionFor(243600)?->offlineSince?->format(DATE_ATOM));
    }

    public function test_device_with_connection_and_location_properties_are_immutable_copies(): void
    {
        $device = new DeviceResponse(1, 'id', 'VUE002', null);
        $connection = new DeviceConnectionResponse(1, true, new DateTimeImmutable('2024-01-01'));
        $properties = new LocationPropertiesResponse(deviceName: 'home');

        $copy = $device->withConnection($connection)->withLocationProperties($properties);

        $this->assertFalse($device->connected);
        $this->assertTrue($copy->connected);
        $this->assertSame('home', $copy->name());
        $this->assertNull($device->name());
    }

    public function test_location_properties_parse_nested_objects(): void
    {
        $properties = LocationPropertiesResponse::from(Fixtures::locationProperties());

        $this->assertSame(40.7128, $properties->latitude);
        $this->assertSame(-74.006, $properties->longitude);
        $this->assertSame(15, $properties->billingCycleStartDay);
        $this->assertSame(15.0, $properties->usageCentPerKwHour);
        $this->assertFalse($properties->solar);
        $this->assertSame('naturalGasFurnace', $properties->locationInformation?->heatSource);

        $empty = LocationPropertiesResponse::from(['latitudeLongitude' => null, 'locationInformation' => null]);
        $this->assertNull($empty->latitude);
        $this->assertNull($empty->locationInformation);
    }

    public function test_outlet_payload(): void
    {
        $outlet = OutletResponse::from(Fixtures::outlet())->withOutletOn(true);

        $this->assertSame(['deviceGid' => 3456, 'outletOn' => true, 'loadGid' => 5678], $outlet->toPayload());
    }

    public function test_charger_maps_fields_and_payload_includes_breaker_pin_only_when_set(): void
    {
        $charger = ChargerResponse::from(Fixtures::charger());

        $this->assertSame('Check your EV', $charger->message);
        $this->assertSame('CarConnected', $charger->icon);
        $this->assertSame('311', $charger->debugCode);
        $this->assertArrayNotHasKey('breakerPIN', $charger->toPayload());

        $withPin = ChargerResponse::from(['breakerPIN' => '1234'] + Fixtures::charger());
        $this->assertSame('1234', $withPin->toPayload()['breakerPIN']);
        $this->assertArrayNotHasKey('breakerPin', $withPin->toArray());

        $modified = $charger->withChargerOn(false)->withChargingRate(16)->withMaxChargingRate(32);
        $this->assertSame([false, 16, 32], [$modified->chargerOn, $modified->chargingRate, $modified->maxChargingRate]);
        $this->assertSame('Check your EV', $modified->message);
    }

    public function test_device_status(): void
    {
        $status = DeviceStatusResponse::from(Fixtures::devicesStatus());

        $this->assertFalse($status->connectionFor(4567)?->connected);
        $this->assertNull($status->connectionFor(1));
        $this->assertSame(['outlets', 'evChargers', 'devicesConnected'], array_keys($status->toArray()));
    }

    public function test_channel_usage_watts_requires_kwh(): void
    {
        $usage = new DeviceChannelUsageResponse(1, '1', 'Oven', 0.5, 50.0, null, Scale::HOUR, Unit::DOLLARS);

        $this->expectException(InvalidArgumentException::class);

        $usage->watts();
    }

    public function test_channel_usage_watts_for_null_usage(): void
    {
        $usage = new DeviceChannelUsageResponse(1, '1', 'Oven', null, null, null, Scale::SECOND, Unit::KILOWATT_HOURS);

        $this->assertNull($usage->watts());
    }

    public function test_chart_usage_without_first_instant_falls_back_to_start(): void
    {
        $start = new DateTimeImmutable('2024-01-01T00:00:00Z');

        $chart = ChartUsageResponse::from(['usageList' => [1, 'x', null]], 1, '1', Scale::MONTH, Unit::KILOWATT_HOURS, $start);

        $this->assertSame([1.0, null, null], $chart->usage);
        $this->assertSame('2024-03-01T00:00:00+00:00', $chart->points()[2]['time']->format(DATE_ATOM));
        $this->assertSame([], (new ChartUsageResponse(1, '1', null, Scale::DAY, Unit::KILOWATT_HOURS, [1.0]))->points());
    }

    public function test_chart_usage_watts_rejects_calendar_scales(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ChartUsageResponse(1, '1', null, Scale::MONTH, Unit::KILOWATT_HOURS, [1.0]))->watts();
    }

    public function test_chart_usage_watts_requires_kwh(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ChartUsageResponse(1, '1', null, Scale::HOUR, Unit::CARBON, [1.0]))->watts();
    }

    public function test_vehicle_status_reads_settings(): void
    {
        $status = VehicleStatusResponse::from(Fixtures::vehicleStatus());

        $this->assertSame(789, $status->vehicleGid);
        $this->assertSame(45, $status->minutesToFullCharge);
        $this->assertSame(48.0, $status->chargeCurrentRequestMax);
        $this->assertSame(0, VehicleStatusResponse::from([])->vehicleGid);
    }

    public function test_device_channel_defaults(): void
    {
        $channel = DeviceChannelResponse::from([]);

        $this->assertSame('1,2,3', $channel->channelNum);
        $this->assertSame(1.0, $channel->channelMultiplier);
        $this->assertSame($channel->toArray(), $channel->toPayload());
    }

    /** @return iterable<string, array{ResponseContract}> */
    public static function responses(): iterable
    {
        yield 'customer' => [CustomerResponse::from(Fixtures::customer())];
        yield 'device' => [DeviceResponse::from(Fixtures::devices()['devices'][0])];
        yield 'status' => [DeviceStatusResponse::from(Fixtures::devicesStatus())];
        yield 'channel type' => [ChannelTypeResponse::from(Fixtures::channelTypes()[0])];
        yield 'vehicle' => [VehicleResponse::from(Fixtures::vehicles()[0])];
        yield 'vehicle status' => [VehicleStatusResponse::from(Fixtures::vehicleStatus())];
        yield 'usage' => [UsageDeviceResponse::from(Fixtures::deviceListUsages()['deviceListUsages']['devices'][0], new DateTimeImmutable, Scale::MINUTE, Unit::KILOWATT_HOURS)];
        yield 'chart' => [ChartUsageResponse::from(Fixtures::chartUsage(), 1, '1', Scale::HOUR, Unit::KILOWATT_HOURS)];
    }

    #[DataProvider('responses')]
    public function test_responses_serialize_to_json(ResponseContract $response): void
    {
        $this->assertSame(json_encode($response->toArray(), JSON_THROW_ON_ERROR), json_encode($response, JSON_THROW_ON_ERROR));
    }
}
