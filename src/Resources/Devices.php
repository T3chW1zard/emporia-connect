<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Responses\DeviceResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceStatusResponse;

/**
 * Device resource — list, find, status, and location properties.
 */
final readonly class Devices
{
    public function __construct(private TransporterContract $transporter) {}

    /** @return DeviceResponse[] */
    public function all(): array
    {
        $data = $this->transporter->get('customers/devices');
        /** @var array<int, array<string, mixed>> $items */
        $items = is_array($data['devices'] ?? null) ? $data['devices'] : [];

        return array_map(DeviceResponse::from(...), $items);
    }

    public function find(int $deviceGid): ?DeviceResponse
    {
        foreach ($this->all() as $device) {
            if ($device->deviceGid === $deviceGid) {
                return $device;
            }
        }

        return null;
    }

    public function status(): DeviceStatusResponse
    {
        return DeviceStatusResponse::from($this->transporter->get('customers/devices/status'));
    }

    public function properties(int $deviceGid): DeviceResponse
    {
        return DeviceResponse::from($this->transporter->get("devices/{$deviceGid}/locationProperties"));
    }
}
