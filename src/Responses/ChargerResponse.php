<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Represents an Emporia EV charger device.
 */
readonly class ChargerResponse
{
    public function __construct(
        public int $deviceGid,
        public bool $chargerOn,
        public ?string $status,
        public ?float $chargingRate,
        public ?float $maxChargingRate,
        public bool $offPeakSchedulesEnabled,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        /** @var array<string, mixed> $ev */
        $ev = DataExtractor::array($data, 'evCharger');

        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            chargerOn: DataExtractor::bool($ev, 'chargerOn'),
            status: DataExtractor::nullableString($ev, 'status'),
            chargingRate: DataExtractor::nullableFloat($ev, 'chargingRate'),
            maxChargingRate: DataExtractor::nullableFloat($ev, 'maxChargingRate'),
            offPeakSchedulesEnabled: DataExtractor::bool($ev, 'offPeakSchedulesEnabled'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'deviceGid'               => $this->deviceGid,
            'chargerOn'               => $this->chargerOn,
            'status'                  => $this->status,
            'chargingRate'            => $this->chargingRate,
            'maxChargingRate'         => $this->maxChargingRate,
            'offPeakSchedulesEnabled' => $this->offPeakSchedulesEnabled,
        ];
    }
}
