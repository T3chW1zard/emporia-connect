<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Battery and charging state of a linked vehicle.
 */
final readonly class VehicleStatusResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $vehicleGid,
        public ?string $vehicleState = null,
        public ?float $batteryLevel = null,
        public ?float $batteryRange = null,
        public ?string $chargingState = null,
        public ?float $chargeLimitPercent = null,
        public ?int $minutesToFullCharge = null,
        public ?float $chargeCurrentRequest = null,
        public ?float $chargeCurrentRequestMax = null,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data  the full response; values live under "settings"
     */
    public static function from(array $data): self
    {
        $settings = DataExtractor::object($data, 'settings') ?? [];

        return new self(
            vehicleGid: DataExtractor::int($settings, 'vehicleGid'),
            vehicleState: DataExtractor::nullableString($settings, 'vehicleState'),
            batteryLevel: DataExtractor::nullableFloat($settings, 'batteryLevel'),
            batteryRange: DataExtractor::nullableFloat($settings, 'batteryRange'),
            chargingState: DataExtractor::nullableString($settings, 'chargingState'),
            chargeLimitPercent: DataExtractor::nullableFloat($settings, 'chargeLimitPercent'),
            minutesToFullCharge: DataExtractor::nullableInt($settings, 'minutesToFullCharge'),
            chargeCurrentRequest: DataExtractor::nullableFloat($settings, 'chargeCurrentRequest'),
            chargeCurrentRequestMax: DataExtractor::nullableFloat($settings, 'chargeCurrentRequestMax'),
        );
    }

    public function toArray(): array
    {
        return [
            'vehicleGid' => $this->vehicleGid,
            'vehicleState' => $this->vehicleState,
            'batteryLevel' => $this->batteryLevel,
            'batteryRange' => $this->batteryRange,
            'chargingState' => $this->chargingState,
            'chargeLimitPercent' => $this->chargeLimitPercent,
            'minutesToFullCharge' => $this->minutesToFullCharge,
            'chargeCurrentRequest' => $this->chargeCurrentRequest,
            'chargeCurrentRequestMax' => $this->chargeCurrentRequestMax,
        ];
    }
}
