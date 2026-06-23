<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Responses\ChargerResponse;

/**
 * EV charger resource — list and control.
 */
final readonly class Chargers
{
    public function __construct(private TransporterContract $transporter) {}

    /** @return ChargerResponse[] */
    public function all(): array
    {
        $data = $this->transporter->get('customers/devices/status');
        /** @var array<int, array<string, mixed>> $items */
        $items = is_array($data['evChargers'] ?? null) ? $data['evChargers'] : [];

        return array_map(ChargerResponse::from(...), $items);
    }

    public function update(int $deviceGid, bool $on, ?float $chargeRate = null): ChargerResponse
    {
        $evPayload = ['chargerOn' => $on];
        if ($chargeRate !== null) {
            $evPayload['chargingRate'] = $chargeRate;
        }

        $data = $this->transporter->put('devices/evcharger', [
            'deviceGid' => $deviceGid,
            'evCharger' => $evPayload,
        ]);

        return ChargerResponse::from($data);
    }
}
