<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Resources;

use DateTimeImmutable;
use DateTimeZone;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Resources\Usage;
use T3chW1zard\EmporiaConnect\Responses\DeviceChannelResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceChannelUsageResponse;
use T3chW1zard\EmporiaConnect\Testing\FakeTransporter;
use T3chW1zard\EmporiaConnect\Testing\Fixtures;
use T3chW1zard\EmporiaConnect\Tests\Support\FrozenClock;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class UsageTest extends TestCase
{
    private FakeTransporter $transporter;

    private Usage $usage;

    protected function setUp(): void
    {
        $this->transporter = new FakeTransporter;
        $this->usage = new Usage($this->transporter, new FrozenClock('2024-06-01T12:00:00Z'));
    }

    public function test_device_list_usage_builds_pyemvue_uri(): void
    {
        $this->usage->devices([2345, 3456], scale: Scale::MINUTE);

        $this->assertSame(
            'AppAPI?apiMethod=getDeviceListUsages&deviceGids=2345+3456&instant=2024-06-01T12:00:00.000Z&scale=1MIN&energyUnit=KilowattHours',
            $this->transporter->sent()[0]['uri'],
        );
    }

    public function test_device_list_usage_accepts_single_gid_and_instant(): void
    {
        $this->usage->devices(2345, new DateTimeImmutable('2024-01-02 03:04:05', new DateTimeZone('Europe/Amsterdam')), Scale::DAY, Unit::DOLLARS);

        $this->assertSame(
            'AppAPI?apiMethod=getDeviceListUsages&deviceGids=2345&instant=2024-01-02T02:04:05.000Z&scale=1D&energyUnit=Dollars',
            $this->transporter->sent()[0]['uri'],
        );
    }

    public function test_device_list_usage_parses_channels_and_nested_devices(): void
    {
        $devices = $this->usage->devices(2345, scale: Scale::MINUTE);

        $this->assertSame([2345], array_keys($devices));
        $device = $devices[2345];
        $this->assertSame('2024-06-01T12:00:00+00:00', $device->timestamp?->format(DATE_ATOM));
        $this->assertSame(['1,2,3', '1', '2', 'Balance'], array_values(array_map(static fn (DeviceChannelUsageResponse $c): string => $c->channelNum, $device->channels)));
        $this->assertSame('Kitchen', $device->channel('1')?->name);

        $mains = $device->channel('1,2,3');
        $this->assertInstanceOf(DeviceChannelUsageResponse::class, $mains);
        $this->assertSame(0.05, $mains->usage);
        $this->assertSame(3000.0, $mains->watts());
        $this->assertSame(Scale::MINUTE, $mains->scale);
        $this->assertSame(0.005, $mains->nestedDevices[3456]->channel('1,2,3')?->usage);
    }

    public function test_device_list_usage_retries_while_data_is_missing(): void
    {
        $incomplete = Fixtures::deviceListUsages();
        $incomplete['deviceListUsages']['devices'][0]['channelUsages'][1]['usage'] = null;
        $responses = [$incomplete, $incomplete, Fixtures::deviceListUsages()];

        $this->transporter->respondWith('GET AppAPI?apiMethod=getDeviceListUsages*', static function () use (&$responses): array {
            return array_shift($responses) ?? [];
        });

        $devices = $this->usage->devices(2345, initialRetryDelay: 0, maxRetryDelay: 0);

        $this->assertCount(3, $this->transporter->sent());
        $this->assertSame(0.01, $devices[2345]->channel('1')?->usage);
        $this->assertFalse($devices[2345]->hasMissingData());
    }

    public function test_device_list_usage_returns_partial_data_after_last_attempt(): void
    {
        $incomplete = Fixtures::deviceListUsages();
        $incomplete['deviceListUsages']['devices'][0]['channelUsages'][1]['usage'] = null;
        $this->transporter->respondWith('GET AppAPI?apiMethod=getDeviceListUsages*', $incomplete);

        $devices = $this->usage->devices(2345, maxRetryAttempts: 2, initialRetryDelay: 0);

        $this->assertCount(2, $this->transporter->sent());
        $this->assertNull($devices[2345]->channel('1')?->usage);
        $this->assertTrue($devices[2345]->hasMissingData());
    }

    public function test_device_list_usage_retries_malformed_responses(): void
    {
        $this->transporter->respondWith('GET AppAPI?apiMethod=getDeviceListUsages*', ['unexpected' => true]);

        $this->assertSame([], $this->usage->devices(2345, maxRetryAttempts: 3, initialRetryDelay: 0));
        $this->assertCount(3, $this->transporter->sent());
    }

    public function test_chart_usage_builds_pyemvue_uri(): void
    {
        $this->usage->chart(2345, '1,2,3', '2024-05-01T00:00:00Z', '2024-06-01T00:00:00Z', Scale::HOUR, Unit::KILOWATT_HOURS);

        $this->assertSame(
            'AppAPI?apiMethod=getChartUsage&deviceGid=2345&channel=1,2,3&start=2024-05-01T00:00:00.000Z&end=2024-06-01T00:00:00.000Z&scale=1H&energyUnit=KilowattHours',
            $this->transporter->sent()[0]['uri'],
        );
    }

    public function test_chart_usage_defaults_to_a_scale_window_ending_now(): void
    {
        $this->usage->chart(2345, '1');

        $this->assertStringContainsString('&start=2024-06-01T00:00:00.000Z&end=2024-06-01T12:00:00.000Z&scale=1MIN', $this->transporter->sent()[0]['uri']);
    }

    public function test_chart_usage_parses_values_and_points(): void
    {
        $chart = $this->usage->chart(2345, '1', scale: Scale::HOUR);

        $this->assertSame([0.5, 0.25, null, 1.0], $chart->usage);
        $this->assertSame(1.75, $chart->total());
        $this->assertSame([500.0, 250.0, null, 1000.0], $chart->watts());

        $points = $chart->points();
        $this->assertCount(4, $points);
        $this->assertSame('2024-06-01T00:00:00+00:00', $points[0]['time']->format(DATE_ATOM));
        $this->assertSame('2024-06-01T03:00:00+00:00', $points[3]['time']->format(DATE_ATOM));
    }

    public function test_chart_usage_for_mains_from_grid_returns_empty_without_request(): void
    {
        $chart = $this->usage->chart(2345, 'MainsFromGrid', '2024-06-01T00:00:00Z');

        $this->assertSame([], $chart->usage);
        $this->assertSame('2024-06-01T00:00:00+00:00', $chart->firstUsageInstant?->format(DATE_ATOM));
        $this->assertSame([], $this->transporter->sent());
    }

    public function test_chart_for_channel_object(): void
    {
        $this->usage->chartForChannel(new DeviceChannelResponse(42, 'Oven', '3'), scale: Scale::DAY);

        $this->assertStringContainsString('deviceGid=42&channel=3&', $this->transporter->sent()[0]['uri']);
    }
}
