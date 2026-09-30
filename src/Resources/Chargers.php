<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Responses\ChargerResponse;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Emporia EV chargers.
 */
final readonly class Chargers
{
    public function __construct(private TransporterContract $transporter) {}

    /**
     * All EV chargers on the account.
     *
     * @return list<ChargerResponse>
     */
    public function all(): array
    {
        return array_map(ChargerResponse::from(...), DataExtractor::objects($this->transporter->get('customers/devices/status'), 'evChargers'));
    }

    public function find(int $deviceGid): ?ChargerResponse
    {
        foreach ($this->all() as $charger) {
            if ($charger->deviceGid === $deviceGid) {
                return $charger;
            }
        }

        return null;
    }

    /**
     * Save the charger state, optionally switching it on/off and changing the charge rate
     * in amps first. A charge rate of 0 is ignored.
     */
    public function update(ChargerResponse $charger, ?bool $on = null, ?int $chargeRate = null): ChargerResponse
    {
        if ($on !== null) {
            $charger = $charger->withChargerOn($on);
        }

        if ($chargeRate !== null && $chargeRate > 0) {
            $charger = $charger->withChargingRate($chargeRate);
        }

        $data = $this->transporter->put('devices/evcharger', $charger->toPayload());

        return $data === [] ? $charger : ChargerResponse::from($data);
    }

    public function turnOn(ChargerResponse $charger): ChargerResponse
    {
        return $this->update($charger, true);
    }

    public function turnOff(ChargerResponse $charger): ChargerResponse
    {
        return $this->update($charger, false);
    }
}
