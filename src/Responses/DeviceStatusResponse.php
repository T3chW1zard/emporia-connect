<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Outlets, chargers and online status of every device (GET customers/devices/status).
 */
final readonly class DeviceStatusResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        /** @var list<OutletResponse> */
        public array $outlets = [],
        /** @var list<ChargerResponse> */
        public array $chargers = [],
        /** @var list<DeviceConnectionResponse> */
        public array $devicesConnected = [],
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            outlets: array_map(OutletResponse::from(...), DataExtractor::objects($data, 'outlets')),
            chargers: array_map(ChargerResponse::from(...), DataExtractor::objects($data, 'evChargers')),
            devicesConnected: array_map(DeviceConnectionResponse::from(...), DataExtractor::objects($data, 'devicesConnected')),
        );
    }

    public function connectionFor(int $deviceGid): ?DeviceConnectionResponse
    {
        foreach ($this->devicesConnected as $connection) {
            if ($connection->deviceGid === $deviceGid) {
                return $connection;
            }
        }

        return null;
    }

    /**
     * Return the given devices with their online status updated.
     *
     * @param  list<DeviceResponse>  $devices
     * @return list<DeviceResponse>
     */
    public function applyTo(array $devices): array
    {
        return array_map(function (DeviceResponse $device): DeviceResponse {
            $connection = $this->connectionFor($device->deviceGid);

            return $connection instanceof DeviceConnectionResponse ? $device->withConnection($connection) : $device;
        }, $devices);
    }

    public function toArray(): array
    {
        return [
            'outlets' => array_map(static fn (OutletResponse $o): array => $o->toArray(), $this->outlets),
            'evChargers' => array_map(static fn (ChargerResponse $c): array => $c->toArray(), $this->chargers),
            'devicesConnected' => array_map(static fn (DeviceConnectionResponse $d): array => $d->toArray(), $this->devicesConnected),
        ];
    }
}
