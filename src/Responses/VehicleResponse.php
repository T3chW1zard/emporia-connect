<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Represents a vehicle linked to an Emporia account.
 */
readonly class VehicleResponse
{
    public function __construct(
        public int $vehicleGid,
        public ?string $vendor,
        public ?string $displayName,
        public ?string $make,
        public ?string $model,
        public ?int $year,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            vehicleGid: DataExtractor::int($data, 'vehicleGid'),
            vendor: DataExtractor::nullableString($data, 'vendor'),
            displayName: DataExtractor::nullableString($data, 'displayName'),
            make: DataExtractor::nullableString($data, 'make'),
            model: DataExtractor::nullableString($data, 'model'),
            year: DataExtractor::nullableInt($data, 'year'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'vehicleGid' => $this->vehicleGid,
            'vendor' => $this->vendor,
            'displayName' => $this->displayName,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
        ];
    }
}
