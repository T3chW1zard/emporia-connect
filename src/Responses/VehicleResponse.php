<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * A vehicle linked to the Emporia account.
 */
final readonly class VehicleResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $vehicleGid,
        public ?string $vendor = null,
        public ?string $apiId = null,
        public ?string $displayName = null,
        public ?int $loadGid = null,
        public ?string $make = null,
        public ?string $model = null,
        public ?int $year = null,
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            vehicleGid: DataExtractor::int($data, 'vehicleGid'),
            vendor: DataExtractor::nullableString($data, 'vendor'),
            apiId: DataExtractor::nullableString($data, 'apiId'),
            displayName: DataExtractor::nullableString($data, 'displayName'),
            loadGid: DataExtractor::nullableInt($data, 'loadGid'),
            make: DataExtractor::nullableString($data, 'make'),
            model: DataExtractor::nullableString($data, 'model'),
            year: DataExtractor::nullableInt($data, 'year'),
        );
    }

    public function toArray(): array
    {
        return [
            'vehicleGid' => $this->vehicleGid,
            'vendor' => $this->vendor,
            'apiId' => $this->apiId,
            'displayName' => $this->displayName,
            'loadGid' => $this->loadGid,
            'make' => $this->make,
            'model' => $this->model,
            'year' => $this->year,
        ];
    }
}
