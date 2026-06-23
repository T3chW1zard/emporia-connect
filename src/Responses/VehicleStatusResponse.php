<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Represents the real-time status of a linked vehicle.
 */
readonly class VehicleStatusResponse
{
    public function __construct(
        public int $vehicleGid,
        public ?string $vehicleState,
        public ?float $batteryLevel,
        public ?float $batteryRange,
        public ?string $chargingState,
        public ?float $chargeLimitPercent,
        public ?int $minutesToFullCharge,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            vehicleGid: DataExtractor::int($data, 'vehicleGid'),
            vehicleState: DataExtractor::nullableString($data, 'vehicleState'),
            batteryLevel: DataExtractor::nullableFloat($data, 'batteryLevel'),
            batteryRange: DataExtractor::nullableFloat($data, 'batteryRange'),
            chargingState: DataExtractor::nullableString($data, 'chargingState'),
            chargeLimitPercent: DataExtractor::nullableFloat($data, 'chargeLimitPercent'),
            minutesToFullCharge: DataExtractor::nullableInt($data, 'minutesToFullCharge'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'vehicleGid'          => $this->vehicleGid,
            'vehicleState'        => $this->vehicleState,
            'batteryLevel'        => $this->batteryLevel,
            'batteryRange'        => $this->batteryRange,
            'chargingState'       => $this->chargingState,
            'chargeLimitPercent'  => $this->chargeLimitPercent,
            'minutesToFullCharge' => $this->minutesToFullCharge,
        ];
    }
}
