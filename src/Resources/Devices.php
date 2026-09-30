<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Responses\DeviceResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceStatusResponse;
use T3chW1zard\EmporiaConnect\Responses\LocationPropertiesResponse;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Devices on the account: Vue monitors, smart plugs and EV chargers.
 */
final readonly class Devices
{
    public function __construct(private TransporterContract $transporter) {}

    /**
     * All devices, with nested sub-devices flattened into the list.
     *
     * @return list<DeviceResponse>
     */
    public function all(): array
    {
        $devices = [];

        foreach (DataExtractor::objects($this->transporter->get('customers/devices'), 'devices') as $device) {
            $devices[] = DeviceResponse::from($device);

            foreach (DataExtractor::objects($device, 'devices') as $subDevice) {
                $devices[] = DeviceResponse::from($subDevice);
            }
        }

        return $devices;
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

    /**
     * Location and billing settings of a device (GET devices/{deviceGid}/locationProperties).
     */
    public function locationProperties(int $deviceGid): LocationPropertiesResponse
    {
        return LocationPropertiesResponse::from($this->transporter->get("devices/{$deviceGid}/locationProperties"));
    }

    /**
     * Return the device with its location properties loaded.
     */
    public function populateLocationProperties(DeviceResponse $device): DeviceResponse
    {
        return $device->withLocationProperties($this->locationProperties($device->deviceGid));
    }

    /**
     * Outlets, chargers and online status of all devices.
     */
    public function status(): DeviceStatusResponse
    {
        return DeviceStatusResponse::from($this->transporter->get('customers/devices/status'));
    }

    /**
     * Return the given devices with their online status refreshed.
     *
     * @param  list<DeviceResponse>  $devices
     * @return list<DeviceResponse>
     */
    public function withConnectionStatus(array $devices): array
    {
        return $this->status()->applyTo($devices);
    }
}
