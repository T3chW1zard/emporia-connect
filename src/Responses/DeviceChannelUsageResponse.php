<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\Converter;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Usage of a single channel.
 *
 * $usage is the energy used during one $scale period, in $unit. Smart plugs and sub-panels
 * that hang off this channel are available in $nestedDevices.
 */
final readonly class DeviceChannelUsageResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $deviceGid,
        public string $channelNum,
        public ?string $name,
        public ?float $usage,
        public ?float $percentage,
        public ?DateTimeImmutable $timestamp,
        public Scale $scale,
        public Unit $unit,
        /** @var array<int, UsageDeviceResponse> keyed by device gid */
        public array $nestedDevices = [],
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data, ?DateTimeImmutable $timestamp, Scale $scale, Unit $unit): self
    {
        $nested = [];

        foreach (DataExtractor::objects($data, 'nestedDevices') as $item) {
            $device = UsageDeviceResponse::from($item, $timestamp, $scale, $unit);
            $nested[$device->deviceGid] = $device;
        }

        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            channelNum: DataExtractor::string($data, 'channelNum', '1,2,3'),
            name: DataExtractor::nullableString($data, 'name'),
            usage: DataExtractor::nullableFloat($data, 'usage'),
            percentage: DataExtractor::nullableFloat($data, 'percentage'),
            timestamp: $timestamp,
            scale: $scale,
            unit: $unit,
            nestedDevices: $nested,
        );
    }

    /**
     * Average power in watts during the period. Only available for kWh usage.
     *
     * @throws InvalidArgumentException when the unit is not kWh or the scale has no fixed length
     */
    public function watts(): ?float
    {
        if ($this->unit !== Unit::KILOWATT_HOURS) {
            throw new InvalidArgumentException('Watts can only be derived from KilowattHours usage.');
        }

        return $this->usage === null ? null : Converter::kilowattHoursToWatts($this->usage, $this->scale);
    }

    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'channelNum' => $this->channelNum,
            'name' => $this->name,
            'usage' => $this->usage,
            'percentage' => $this->percentage,
            'timestamp' => $this->timestamp?->format(DateTimeInterface::ATOM),
            'scale' => $this->scale->value,
            'unit' => $this->unit->value,
            'nestedDevices' => array_map(static fn (UsageDeviceResponse $d): array => $d->toArray(), $this->nestedDevices),
        ];
    }
}
