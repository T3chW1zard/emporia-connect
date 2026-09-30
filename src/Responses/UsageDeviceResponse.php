<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use DateTimeImmutable;
use DateTimeInterface;
use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Usage of one device's channels at an instant (PyEmVue: VueUsageDevice).
 */
final readonly class UsageDeviceResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $deviceGid,
        public ?DateTimeImmutable $timestamp,
        public Scale $scale,
        public Unit $unit,
        /** @var array<array-key, DeviceChannelUsageResponse> keyed by channel number (numeric numbers become int keys) */
        public array $channels = [],
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data, ?DateTimeImmutable $timestamp, Scale $scale, Unit $unit): self
    {
        $channels = [];

        foreach (DataExtractor::objects($data, 'channelUsages') as $item) {
            $channel = DeviceChannelUsageResponse::from($item, $timestamp, $scale, $unit);
            $channels[$channel->channelNum] = $channel;
        }

        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            timestamp: $timestamp,
            scale: $scale,
            unit: $unit,
            channels: $channels,
        );
    }

    public function channel(string $channelNum): ?DeviceChannelUsageResponse
    {
        return $this->channels[$channelNum] ?? null;
    }

    /**
     * Whether any channel is missing its value (the API sometimes returns null for recent data).
     */
    public function hasMissingData(): bool
    {
        foreach ($this->channels as $channel) {
            if ($channel->usage === null) {
                return true;
            }
        }

        return false;
    }

    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'timestamp' => $this->timestamp?->format(DateTimeInterface::ATOM),
            'scale' => $this->scale->value,
            'unit' => $this->unit->value,
            'channels' => array_map(static fn (DeviceChannelUsageResponse $c): array => $c->toArray(), $this->channels),
        ];
    }
}
