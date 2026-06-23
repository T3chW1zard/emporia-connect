<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Support\Converter;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Represents a channel on an Emporia device with current usage.
 */
readonly class DeviceChannelResponse
{
    public function __construct(
        public int $deviceGid,
        public string $channelNum,
        public string $name,
        public float $currentUsage,
        public Unit $unit,
        public Scale $scale,
        public ?float $percentage,
        public ?int $channelTypeGid,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data, Unit $unit, Scale $scale): self
    {
        $shouldConvert = $unit === Unit::KILOWATT_HOURS
            && in_array($scale, [Scale::SECOND, Scale::MINUTE], true);

        $rawUsage = DataExtractor::float($data, 'usage');
        $usage    = (float) Converter::toPreciseFloat($rawUsage, 3);

        if ($shouldConvert) {
            $usage = Converter::toWatts($usage, $scale);
        }

        $rawPercentage = DataExtractor::nullableFloat($data, 'percentage');
        $percentage    = $rawPercentage !== null ? (float) Converter::toPreciseFloat($rawPercentage, 0) : null;

        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            channelNum: DataExtractor::string($data, 'channelNum'),
            name: DataExtractor::string($data, 'name'),
            currentUsage: $usage,
            unit: $unit,
            scale: $scale,
            percentage: $percentage,
            channelTypeGid: DataExtractor::nullableInt($data, 'channelTypeGid'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'deviceGid'      => $this->deviceGid,
            'channelNum'     => $this->channelNum,
            'name'           => $this->name,
            'currentUsage'   => $this->currentUsage,
            'unit'           => $this->unit->value,
            'scale'          => $this->scale->value,
            'percentage'     => $this->percentage,
            'channelTypeGid' => $this->channelTypeGid,
        ];
    }
}
