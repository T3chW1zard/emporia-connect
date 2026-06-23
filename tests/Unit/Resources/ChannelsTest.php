<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Resources;

use T3chW1zard\EmporiaConnect\Responses\DeviceChannelResponse;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Resources\Channels;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class ChannelsTest extends TestCase
{
    private function makeTransporter(array $getReturn): TransporterContract
    {
        return new class ($getReturn) implements TransporterContract {
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

    public function test_all_returns_channel_array(): void
    {
        $channels = (new Channels($this->makeTransporter([
            'deviceListUsages' => ['devices' => [['channelUsages' => [
                ['deviceGid' => 1, 'channelNum' => '1', 'name' => 'Main', 'usage' => 0.001, 'percentage' => null, 'channelTypeGid' => null],
            ]]]],
        ])))->all(1, Scale::HOUR);

        $this->assertCount(1, $channels);
        $this->assertSame('1', $channels[0]->channelNum);
    }

    public function test_find_returns_null_when_not_found(): void
    {
        $this->assertNotInstanceOf(DeviceChannelResponse::class, (new Channels($this->makeTransporter([
            'deviceListUsages' => ['devices' => [['channelUsages' => []]]],
        ])))->find(1, '99', Scale::HOUR));
    }

    public function test_usage_returns_channel_usage_response(): void
    {
        $usage = (new Channels($this->makeTransporter([
            'firstUsageInstant' => '2024-06-01T00:00:00Z',
            'usageList'         => [0.001, 0.002],
        ])))->usage(1, '1', Scale::HOUR, Unit::KILOWATT_HOURS);

        $this->assertCount(2, $usage->usage);
    }
}
