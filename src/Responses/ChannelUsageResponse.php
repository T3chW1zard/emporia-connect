<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use DateTimeImmutable;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Support\Converter;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Represents time-series energy usage for a channel.
 */
readonly class ChannelUsageResponse
{
    public function __construct(
        public string $channelNum,
        public string $firstUsageInstant,
        public Scale $scale,
        public Unit $unit,
        /** @var array<int, array{time: string, usage: float}|float> */
        public array $usage,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(
        array $data,
        string $channelNum,
        Unit $unit,
        Scale $scale,
        bool $withTimestamps = true,
    ): self {
        $shouldConvert = $unit === Unit::KILOWATT_HOURS
            && in_array($scale, [Scale::SECOND, Scale::MINUTE], true);

        $modifier = match ($scale) {
            Scale::SECOND     => '+1 second',
            Scale::MINUTE     => '+1 minute',
            Scale::MINUTES_15 => '+15 minutes',
            Scale::HOUR       => '+1 hour',
            Scale::DAY        => '+1 day',
            Scale::WEEK       => '+1 week',
            Scale::MONTH      => '+1 month',
            Scale::YEAR       => '+1 year',
        };

        $firstInstant = DataExtractor::string($data, 'firstUsageInstant', 'now');
        $currentTime  = new DateTimeImmutable($firstInstant);
        $usages       = [];

        foreach (DataExtractor::array($data, 'usageList') as $raw) {
            $value = (float) Converter::toPreciseFloat(is_scalar($raw) ? (float) $raw : 0.0);
            if ($shouldConvert) {
                $value = Converter::toWatts($value, $scale);
            }
            $usages[] = $withTimestamps ? ['time' => $currentTime->format('Y-m-d\TH:i:s\Z'), 'usage' => $value] : $value;
            $currentTime = $currentTime->modify($modifier);
        }

        return new self(
            channelNum: $channelNum,
            firstUsageInstant: $firstInstant,
            scale: $scale,
            unit: $shouldConvert ? Unit::KILOWATT_HOURS : $unit,
            usage: $usages,
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'channelNum'        => $this->channelNum,
            'firstUsageInstant' => $this->firstUsageInstant,
            'scale'             => $this->scale->value,
            'unit'              => $this->unit->value,
            'usage'             => $this->usage,
        ];
    }
}
