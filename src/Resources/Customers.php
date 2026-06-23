<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Responses\CustomerResponse;
use T3chW1zard\EmporiaConnect\Responses\VehicleResponse;
use T3chW1zard\EmporiaConnect\Responses\VehicleStatusResponse;

/**
 * Customer and vehicle resource.
 */
final readonly class Customers
{
    public function __construct(private TransporterContract $transporter) {}

    public function me(): CustomerResponse
    {
        return CustomerResponse::from($this->transporter->get('customers'));
    }

    /** @return VehicleResponse[] */
    public function vehicles(): array
    {
        $data = $this->transporter->get('customers/vehicles');
        /** @var array<int, array<string, mixed>> $items */
        $items = is_array($data['vehicles'] ?? null) ? $data['vehicles'] : [];

        return array_map(VehicleResponse::from(...), $items);
    }

    public function vehicleStatus(int $vehicleGid): ?VehicleStatusResponse
    {
        $data = $this->transporter->get("vehicles/v2/settings?vehicleGid={$vehicleGid}");

        return isset($data['vehicleGid']) ? VehicleStatusResponse::from($data) : null;
    }
}
