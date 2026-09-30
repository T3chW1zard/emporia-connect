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
 * Usage of a channel over a time range.
 *
 * $usage holds one value per $scale period starting at $firstUsageInstant; null means no data.
 */
final readonly class ChartUsageResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $deviceGid,
        public string $channelNum,
        public ?DateTimeImmutable $firstUsageInstant,
        public Scale $scale,
        public Unit $unit,
        /** @var list<float|null> */
        public array $usage = [],
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data, int $deviceGid, string $channelNum, Scale $scale, Unit $unit, ?DateTimeImmutable $start = null): self
    {
        $usage = [];

        foreach (DataExtractor::array($data, 'usageList') as $value) {
            $usage[] = is_int($value) || is_float($value) ? (float) $value : null;
        }

        return new self(
            deviceGid: $deviceGid,
            channelNum: $channelNum,
            firstUsageInstant: DataExtractor::nullableDate($data, 'firstUsageInstant') ?? $start,
            scale: $scale,
            unit: $unit,
            usage: $usage,
        );
    }

    /**
     * Usage values paired with the start time of their period.
     *
     * @return list<array{time: DateTimeImmutable, usage: float|null}>
     */
    public function points(): array
    {
        if (! $this->firstUsageInstant instanceof DateTimeImmutable) {
            return [];
        }

        $points = [];
        $time = $this->firstUsageInstant;

        foreach ($this->usage as $value) {
            $points[] = ['time' => $time, 'usage' => $value];
            $time = $time->modify($this->scale->modifier());
        }

        return $points;
    }

    /**
     * Average power in watts for each period. Only available for kWh usage.
     *
     * @return list<float|null>
     *
     * @throws InvalidArgumentException when the unit is not kWh or the scale has no fixed length
     */
    public function watts(): array
    {
        if ($this->unit !== Unit::KILOWATT_HOURS) {
            throw new InvalidArgumentException('Watts can only be derived from KilowattHours usage.');
        }

        return array_map(
            fn (?float $value): ?float => $value === null ? null : Converter::kilowattHoursToWatts($value, $this->scale),
            $this->usage,
        );
    }

    public function total(): float
    {
        return array_sum(array_filter($this->usage, static fn (?float $value): bool => $value !== null));
    }

    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'channelNum' => $this->channelNum,
            'firstUsageInstant' => $this->firstUsageInstant?->format(DateTimeInterface::ATOM),
            'scale' => $this->scale->value,
            'unit' => $this->unit->value,
            'usage' => $this->usage,
            'points' => array_map(static fn (array $point): array => [
                'time' => $point['time']->format(DateTimeInterface::ATOM),
                'usage' => $point['usage'],
            ], $this->points()),
        ];
    }
}
