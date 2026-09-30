<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Location and billing settings of a device (GET devices/{deviceGid}/locationProperties).
 */
final readonly class LocationPropertiesResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public ?int $deviceGid = null,
        public ?string $deviceName = null,
        public ?string $displayName = null,
        public ?string $zipCode = null,
        public ?string $timeZone = null,
        public ?int $billingCycleStartDay = null,
        public ?float $usageCentPerKwHour = null,
        public ?float $peakDemandDollarPerKw = null,
        public ?bool $solar = null,
        public ?int $utilityRateGid = null,
        public ?LocationInformationResponse $locationInformation = null,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        $information = DataExtractor::object($data, 'locationInformation');
        $coordinates = DataExtractor::object($data, 'latitudeLongitude') ?? [];

        return new self(
            deviceGid: DataExtractor::nullableInt($data, 'deviceGid'),
            deviceName: DataExtractor::nullableString($data, 'deviceName'),
            displayName: DataExtractor::nullableString($data, 'displayName'),
            zipCode: DataExtractor::nullableString($data, 'zipCode'),
            timeZone: DataExtractor::nullableString($data, 'timeZone'),
            billingCycleStartDay: DataExtractor::nullableInt($data, 'billingCycleStartDay'),
            usageCentPerKwHour: DataExtractor::nullableFloat($data, 'usageCentPerKwHour'),
            peakDemandDollarPerKw: DataExtractor::nullableFloat($data, 'peakDemandDollarPerKw'),
            solar: DataExtractor::nullableBool($data, 'solar'),
            utilityRateGid: DataExtractor::nullableInt($data, 'utilityRateGid'),
            locationInformation: $information === null ? null : LocationInformationResponse::from($information),
            latitude: DataExtractor::nullableFloat($coordinates, 'latitude'),
            longitude: DataExtractor::nullableFloat($coordinates, 'longitude'),
        );
    }

    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'deviceName' => $this->deviceName,
            'displayName' => $this->displayName,
            'zipCode' => $this->zipCode,
            'timeZone' => $this->timeZone,
            'billingCycleStartDay' => $this->billingCycleStartDay,
            'usageCentPerKwHour' => $this->usageCentPerKwHour,
            'peakDemandDollarPerKw' => $this->peakDemandDollarPerKw,
            'solar' => $this->solar,
            'utilityRateGid' => $this->utilityRateGid,
            'locationInformation' => $this->locationInformation?->toArray(),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
