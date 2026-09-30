<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Household details entered in the Emporia app. The API returns every value as a string.
 */
final readonly class LocationInformationResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public ?string $airConditioning = null,
        public ?string $heatSource = null,
        public ?string $locationSqFt = null,
        public ?string $numElectricCars = null,
        public ?string $locationType = null,
        public ?string $numPeople = null,
        public ?string $swimmingPool = null,
        public ?string $hotTub = null,
        public ?string $primaryVehicle = null,
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            airConditioning: DataExtractor::nullableString($data, 'airConditioning'),
            heatSource: DataExtractor::nullableString($data, 'heatSource'),
            locationSqFt: DataExtractor::nullableString($data, 'locationSqFt'),
            numElectricCars: DataExtractor::nullableString($data, 'numElectricCars'),
            locationType: DataExtractor::nullableString($data, 'locationType'),
            numPeople: DataExtractor::nullableString($data, 'numPeople'),
            swimmingPool: DataExtractor::nullableString($data, 'swimmingPool'),
            hotTub: DataExtractor::nullableString($data, 'hotTub'),
            primaryVehicle: DataExtractor::nullableString($data, 'primaryVehicle'),
        );
    }

    public function toArray(): array
    {
        return [
            'airConditioning' => $this->airConditioning,
            'heatSource' => $this->heatSource,
            'locationSqFt' => $this->locationSqFt,
            'numElectricCars' => $this->numElectricCars,
            'locationType' => $this->locationType,
            'numPeople' => $this->numPeople,
            'swimmingPool' => $this->swimmingPool,
            'hotTub' => $this->hotTub,
            'primaryVehicle' => $this->primaryVehicle,
        ];
    }
}
