<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Responses\VehicleResponse;
use T3chW1zard\EmporiaConnect\Responses\VehicleStatusResponse;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Vehicles linked to the account.
 */
final readonly class Vehicles
{
    public function __construct(private TransporterContract $transporter) {}

    /**
     * All linked vehicles (PyEmVue: get_vehicles).
     *
     * @return list<VehicleResponse>
     */
    public function all(): array
    {
        return array_map(VehicleResponse::from(...), DataExtractor::objects($this->transporter->get('customers/vehicles')));
    }

    /**
     * Battery and charging state of a vehicle, or null when the API returns nothing (PyEmVue: get_vehicle_status).
     */
    public function status(int $vehicleGid): ?VehicleStatusResponse
    {
        $data = $this->transporter->get("vehicles/v2/settings?vehicleGid={$vehicleGid}");

        return $data === [] ? null : VehicleStatusResponse::from($data);
    }
}
