<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use DateTimeImmutable;
use DateTimeInterface;
use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * An Emporia device: Vue energy monitor, smart plug or EV charger.
 */
final readonly class DeviceResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $deviceGid,
        public string $manufacturerDeviceId,
        public string $model,
        public ?string $firmware,
        public ?int $parentDeviceGid = null,
        public ?string $parentChannelNum = null,
        /** @var list<DeviceChannelResponse> */
        public array $channels = [],
        public ?OutletResponse $outlet = null,
        public ?ChargerResponse $evCharger = null,
        public bool $connected = false,
        public ?DateTimeImmutable $offlineSince = null,
        public ?LocationPropertiesResponse $locationProperties = null,
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        $connection = DataExtractor::object($data, 'deviceConnected') ?? [];
        $outlet = DataExtractor::object($data, 'outlet');
        $charger = DataExtractor::object($data, 'evCharger');
        $location = DataExtractor::object($data, 'locationProperties');

        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            manufacturerDeviceId: DataExtractor::string($data, 'manufacturerDeviceId'),
            model: DataExtractor::string($data, 'model'),
            firmware: DataExtractor::nullableString($data, 'firmware'),
            parentDeviceGid: DataExtractor::nullableInt($data, 'parentDeviceGid'),
            parentChannelNum: DataExtractor::nullableString($data, 'parentChannelNum'),
            channels: array_map(DeviceChannelResponse::from(...), DataExtractor::objects($data, 'channels')),
            outlet: $outlet === null ? null : OutletResponse::from($outlet),
            evCharger: $charger === null ? null : ChargerResponse::from($charger),
            connected: DataExtractor::bool($connection, 'connected'),
            offlineSince: DataExtractor::nullableDate($connection, 'offlineSince'),
            locationProperties: $location === null ? null : LocationPropertiesResponse::from($location),
        );
    }

    /**
     * Name shown in the Emporia app.
     */
    public function name(): ?string
    {
        $properties = $this->locationProperties;

        return $properties instanceof LocationPropertiesResponse ? ($properties->displayName ?? $properties->deviceName) : null;
    }

    public function channel(string $channelNum): ?DeviceChannelResponse
    {
        foreach ($this->channels as $channel) {
            if ($channel->channelNum === $channelNum) {
                return $channel;
            }
        }

        return null;
    }

    public function isOutlet(): bool
    {
        return $this->outlet instanceof OutletResponse;
    }

    public function isCharger(): bool
    {
        return $this->evCharger instanceof ChargerResponse;
    }

    /**
     * Copy with location properties attached.
     */
    public function withLocationProperties(LocationPropertiesResponse $locationProperties): self
    {
        return $this->copy(locationProperties: $locationProperties);
    }

    /**
     * Copy with the online status replaced.
     */
    public function withConnection(DeviceConnectionResponse $connection): self
    {
        return $this->copy(connection: $connection);
    }

    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'manufacturerDeviceId' => $this->manufacturerDeviceId,
            'model' => $this->model,
            'firmware' => $this->firmware,
            'parentDeviceGid' => $this->parentDeviceGid,
            'parentChannelNum' => $this->parentChannelNum,
            'channels' => array_map(static fn (DeviceChannelResponse $c): array => $c->toArray(), $this->channels),
            'outlet' => $this->outlet?->toArray(),
            'evCharger' => $this->evCharger?->toArray(),
            'connected' => $this->connected,
            'offlineSince' => $this->offlineSince?->format(DateTimeInterface::ATOM),
            'locationProperties' => $this->locationProperties?->toArray(),
        ];
    }

    private function copy(?LocationPropertiesResponse $locationProperties = null, ?DeviceConnectionResponse $connection = null): self
    {
        return new self(
            deviceGid: $this->deviceGid,
            manufacturerDeviceId: $this->manufacturerDeviceId,
            model: $this->model,
            firmware: $this->firmware,
            parentDeviceGid: $this->parentDeviceGid,
            parentChannelNum: $this->parentChannelNum,
            channels: $this->channels,
            outlet: $this->outlet,
            evCharger: $this->evCharger,
            connected: $connection instanceof DeviceConnectionResponse ? $connection->connected : $this->connected,
            offlineSince: $connection instanceof DeviceConnectionResponse ? $connection->offlineSince : $this->offlineSince,
            locationProperties: $locationProperties ?? $this->locationProperties,
        );
    }
}
