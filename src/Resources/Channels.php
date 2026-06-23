<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use DateTimeImmutable;
use DateTimeZone;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Responses\ChannelTypeResponse;
use T3chW1zard\EmporiaConnect\Responses\ChannelUsageResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceChannelResponse;

/**
 * Channel resource — list, find, usage, types, and update.
 */
final readonly class Channels
{
    public function __construct(private TransporterContract $transporter) {}

    /** @return DeviceChannelResponse[] */
    public function all(int $deviceGid, Scale $scale, Unit $unit = Unit::KILOWATT_HOURS): array
    {
        $instant = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s.000\Z');
        $uri     = sprintf(
            'AppAPI?apiMethod=getDeviceListUsages&deviceGids=%d&instant=%s&scale=%s&energyUnit=%s',
            $deviceGid,
            $instant,
            $scale->value,
            $unit->value,
        );

        $data    = $this->transporter->get($uri);
        $usages  = $data['deviceListUsages'] ?? null;
        /** @var array<int, array<string, mixed>> $devices */
        $devices = is_array($usages) && is_array($usages['devices'] ?? null) ? $usages['devices'] : [];
        $first   = $devices[0] ?? null;
        /** @var array<int, array<string, mixed>> $channels */
        $channels = is_array($first) && is_array($first['channelUsages'] ?? null) ? $first['channelUsages'] : [];

        return array_map(fn(array $u): DeviceChannelResponse => DeviceChannelResponse::from($u, $unit, $scale), $channels);
    }

    public function find(int $deviceGid, string $channelNum, Scale $scale, Unit $unit = Unit::KILOWATT_HOURS): ?DeviceChannelResponse
    {
        foreach ($this->all($deviceGid, $scale, $unit) as $channel) {
            if ($channel->channelNum === $channelNum) {
                return $channel;
            }
        }

        return null;
    }

    public function usage(
        int $deviceGid,
        string $channelNum,
        Scale $scale = Scale::MINUTE,
        Unit $unit = Unit::KILOWATT_HOURS,
        ?string $start = null,
        ?string $end = null,
        bool $withTimestamps = true,
    ): ChannelUsageResponse {
        $defaultStart = match ($scale) {
            Scale::SECOND     => '-1 hour',
            Scale::MINUTE     => '-12 hours',
            Scale::MINUTES_15 => '-1 day',
            Scale::HOUR       => '-1 day',
            Scale::DAY        => '-1 week',
            Scale::WEEK       => '-1 month',
            Scale::MONTH      => '-1 year',
            Scale::YEAR       => '-5 years',
        };

        $now       = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $startTime = ($start !== null ? new DateTimeImmutable($start) : $now->modify($defaultStart))->format('Y-m-d\TH:i:s.000\Z');
        $endTime   = ($end !== null ? new DateTimeImmutable($end) : $now)->format('Y-m-d\TH:i:s.000\Z');

        $uri = sprintf(
            'AppAPI?apiMethod=getChartUsage&deviceGid=%d&channel=%s&start=%s&end=%s&scale=%s&energyUnit=%s',
            $deviceGid,
            $channelNum,
            $startTime,
            $endTime,
            $scale->value,
            $unit->value,
        );

        return ChannelUsageResponse::from($this->transporter->get($uri), $channelNum, $unit, $scale, $withTimestamps);
    }

    /** @return ChannelTypeResponse[] */
    public function types(): array
    {
        $data = $this->transporter->get('devices/channels/channeltypes');
        /** @var array<int, array<string, mixed>> $items */
        $items = is_array($data['channelTypes'] ?? null) ? $data['channelTypes'] : [];

        return array_map(ChannelTypeResponse::from(...), $items);
    }

    /** @param array<string, mixed> $payload */
    public function update(int $deviceGid, string $channelNum, array $payload): DeviceChannelResponse
    {
        $data = $this->transporter->put("devices/{$deviceGid}/channels/{$channelNum}", $payload);

        return DeviceChannelResponse::from($data, Unit::KILOWATT_HOURS, Scale::MINUTE);
    }
}
