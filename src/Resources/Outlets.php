<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Responses\OutletResponse;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Emporia smart plugs.
 */
final readonly class Outlets
{
    public function __construct(private TransporterContract $transporter) {}

    /**
     * All smart plugs on the account (PyEmVue: get_outlets).
     *
     * @return list<OutletResponse>
     */
    public function all(): array
    {
        return array_map(OutletResponse::from(...), DataExtractor::objects($this->transporter->get('customers/devices/status'), 'outlets'));
    }

    public function find(int $deviceGid): ?OutletResponse
    {
        foreach ($this->all() as $outlet) {
            if ($outlet->deviceGid === $deviceGid) {
                return $outlet;
            }
        }

        return null;
    }

    /**
     * Save the outlet state, optionally switching it on/off first (PyEmVue: update_outlet).
     */
    public function update(OutletResponse $outlet, ?bool $on = null): OutletResponse
    {
        if ($on !== null) {
            $outlet = $outlet->withOutletOn($on);
        }

        $data = $this->transporter->put('devices/outlet', $outlet->toPayload());

        return $data === [] ? $outlet : OutletResponse::from($data);
    }

    public function turnOn(OutletResponse $outlet): OutletResponse
    {
        return $this->update($outlet, true);
    }

    public function turnOff(OutletResponse $outlet): OutletResponse
    {
        return $this->update($outlet, false);
    }
}
