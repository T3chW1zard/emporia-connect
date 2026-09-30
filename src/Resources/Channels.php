<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Responses\ChannelTypeResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceChannelResponse;
use T3chW1zard\EmporiaConnect\Responses\DeviceResponse;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Device channels and channel types.
 */
final readonly class Channels
{
    public function __construct(private TransporterContract $transporter) {}

    /**
     * Channels of a device.
     *
     * @return list<DeviceChannelResponse>
     */
    public function all(int $deviceGid): array
    {
        $device = (new Devices($this->transporter))->find($deviceGid);

        return $device instanceof DeviceResponse ? $device->channels : [];
    }

    public function find(int $deviceGid, string $channelNum): ?DeviceChannelResponse
    {
        foreach ($this->all($deviceGid) as $channel) {
            if ($channel->channelNum === $channelNum) {
                return $channel;
            }
        }

        return null;
    }

    /**
     * Available channel types.
     *
     * @return list<ChannelTypeResponse>
     */
    public function types(): array
    {
        return array_map(ChannelTypeResponse::from(...), DataExtractor::objects($this->transporter->get('devices/channels/channeltypes')));
    }

    /**
     * Save a channel's name, multiplier and type.
     *
     * Modify the channel with its with*() methods first, e.g. $channel->withName('Oven').
     */
    public function update(DeviceChannelResponse $channel): DeviceChannelResponse
    {
        $data = $this->transporter->put("devices/{$channel->deviceGid}/channels", $channel->toPayload());

        return $data === [] ? $channel : DeviceChannelResponse::from($data);
    }
}
