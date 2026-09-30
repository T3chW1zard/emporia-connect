<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use DateTimeImmutable;
use DateTimeInterface;
use Psr\Clock\ClockInterface;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Responses\ChartUsageResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceChannelResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceChannelUsageResponse;
use T3chW1zard\EmporiaConnect\Responses\UsageDeviceResponse;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;
use T3chW1zard\EmporiaConnect\Support\SystemClock;
use T3chW1zard\EmporiaConnect\Support\Time;

/**
 * Energy usage: instant usage per device and historical chart data per channel.
 *
 * Values are the energy used during one scale period (e.g. kWh per minute). Use the
 * watts() helpers on the responses to get average power.
 */
final readonly class Usage
{
    /** Channels for which the chart endpoint returns no data; an empty result is returned without a request. */
    private const CHANNELS_WITHOUT_CHART = ['MainsFromGrid', 'MainsToGrid'];

    public function __construct(
        private TransporterContract $transporter,
        private ClockInterface $clock = new SystemClock,
    ) {}

    /**
     * Usage of every channel of one or more devices at an instant.
     *
     * The API sometimes returns null for channels whose latest data is not in yet; the
     * request is then retried with exponential back-off and the most complete result is returned.
     *
     * @param  int|list<int>  $deviceGids
     * @return array<int, UsageDeviceResponse> keyed by device gid
     */
    public function devices(
        int|array $deviceGids,
        DateTimeInterface|string|null $instant = null,
        Scale $scale = Scale::SECOND,
        Unit $unit = Unit::KILOWATT_HOURS,
        int $maxRetryAttempts = 5,
        float $initialRetryDelay = 2.0,
        float $maxRetryDelay = 30.0,
    ): array {
        $uri = sprintf(
            'AppAPI?apiMethod=getDeviceListUsages&deviceGids=%s&instant=%s&scale=%s&energyUnit=%s',
            implode('+', array_map(intval(...), (array) $deviceGids)),
            Time::format($instant ?? $this->clock->now()),
            $scale->value,
            $unit->value,
        );

        $maxRetryAttempts = max(1, $maxRetryAttempts);
        $devices = [];

        for ($attempt = 1; $attempt <= $maxRetryAttempts; $attempt++) {
            if ($attempt > 1) {
                $delay = min($initialRetryDelay * (2 ** ($attempt - 2)), $maxRetryDelay);
                usleep((int) (max(0.0, $delay) * 1_000_000));
            }

            $usages = DataExtractor::object($this->transporter->get($uri), 'deviceListUsages');

            if ($usages === null || ! is_array($usages['devices'] ?? null)) {
                continue;
            }

            $timestamp = DataExtractor::nullableDate($usages, 'instant');
            $complete = true;

            foreach (DataExtractor::objects($usages, 'devices') as $item) {
                $device = UsageDeviceResponse::from($item, $timestamp, $scale, $unit);
                $missing = $device->hasMissingData();
                $complete = $complete && ! $missing;

                // Keep complete data; only fall back to partial data on the last attempt.
                if (! $missing || $attempt === $maxRetryAttempts) {
                    $devices[$device->deviceGid] = $device;
                }
            }

            if ($complete) {
                break;
            }
        }

        return $devices;
    }

    /**
     * Usage of one channel over a time range.
     *
     * When $start is omitted a scale dependent window before $end is used (e.g. 12 hours for minutes).
     */
    public function chart(
        int $deviceGid,
        string $channelNum,
        DateTimeInterface|string|null $start = null,
        DateTimeInterface|string|null $end = null,
        Scale $scale = Scale::MINUTE,
        Unit $unit = Unit::KILOWATT_HOURS,
    ): ChartUsageResponse {
        $endTime = $end === null ? $this->clock->now() : Time::toUtc($end);
        $startTime = $start === null ? $endTime->modify($scale->defaultWindow()) : Time::toUtc($start);

        if (in_array($channelNum, self::CHANNELS_WITHOUT_CHART, true)) {
            return new ChartUsageResponse($deviceGid, $channelNum, DateTimeImmutable::createFromInterface($startTime), $scale, $unit, []);
        }

        $uri = sprintf(
            'AppAPI?apiMethod=getChartUsage&deviceGid=%d&channel=%s&start=%s&end=%s&scale=%s&energyUnit=%s',
            $deviceGid,
            $channelNum,
            Time::format($startTime),
            Time::format($endTime),
            $scale->value,
            $unit->value,
        );

        return ChartUsageResponse::from(
            $this->transporter->get($uri),
            $deviceGid,
            $channelNum,
            $scale,
            $unit,
            DateTimeImmutable::createFromInterface($startTime),
        );
    }

    /**
     * {@see self::chart()} for a channel object returned by the channels or usage endpoints.
     */
    public function chartForChannel(
        DeviceChannelResponse|DeviceChannelUsageResponse $channel,
        DateTimeInterface|string|null $start = null,
        DateTimeInterface|string|null $end = null,
        Scale $scale = Scale::MINUTE,
        Unit $unit = Unit::KILOWATT_HOURS,
    ): ChartUsageResponse {
        return $this->chart($channel->deviceGid, $channel->channelNum, $start, $end, $scale, $unit);
    }
}
