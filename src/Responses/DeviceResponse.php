<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use DateTimeImmutable;
use Exception;
use T3chW1zard\EmporiaConnect\Exceptions\EmporiaException;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Represents an Emporia Vue device (e.g. Vue 2 energy monitor).
 */
readonly class DeviceResponse
{
    public function __construct(
        public int $deviceGid,
        public string $manufacturerDeviceId,
        public string $model,
        public string $firmware,
        public ?int $parentDeviceGid,
        public ?string $parentChannelNum,
        public bool $isConnected,
        public ?DateTimeImmutable $offlineSince,
        /** @var string[] */
        public array $channels,
        public ?string $deviceName,
        public ?string $displayName,
        public ?string $zipCode,
        public ?string $timeZone,
        public ?float $latitude,
        public ?float $longitude,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        /** @var array<string, mixed> $connected */
        $connected = DataExtractor::array($data, 'deviceConnected');
        $offlineSince = null;

        $rawOffline = DataExtractor::nullableString($connected, 'offlineSince');
        if ($rawOffline !== null) {
            $raw = (string) preg_replace('/^since /i', '', $rawOffline);
            try {
                $offlineSince = new DateTimeImmutable($raw);
            } catch (Exception $e) {
                throw new EmporiaException("Invalid offlineSince date '{$raw}': ".$e->getMessage(), $e->getCode(), previous: $e);
            }
        }

        $channels = [];
        /** @var array<int, mixed> $subDevices */
        $subDevices = DataExtractor::array($data, 'devices');

        foreach ($subDevices as $subDevice) {
            if (! is_array($subDevice)) {
                continue;
            }
            /** @var array<string, mixed> $typedDevice */
            $typedDevice = $subDevice;

            foreach (DataExtractor::array($typedDevice, 'channels') as $ch) {
                if (! is_array($ch)) {
                    continue;
                }
                /** @var array<string, mixed> $typedChannel */
                $typedChannel = $ch;
                $channels[] = DataExtractor::string($typedChannel, 'channelNum');
            }
        }

        /** @var array<string, mixed> $locationProperties */
        $locationProperties = DataExtractor::array($data, 'locationProperties');

        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            manufacturerDeviceId: DataExtractor::string($data, 'manufacturerDeviceId'),
            model: DataExtractor::string($data, 'model'),
            firmware: DataExtractor::string($data, 'firmware'),
            parentDeviceGid: DataExtractor::nullableInt($data, 'parentDeviceGid'),
            parentChannelNum: DataExtractor::nullableString($data, 'parentChannelNum'),
            isConnected: DataExtractor::bool($connected, 'connected'),
            offlineSince: $offlineSince,
            channels: $channels,
            deviceName: DataExtractor::nullableString($data, 'deviceName'),
            displayName: DataExtractor::nullableString($data, 'displayName'),
            zipCode: DataExtractor::nullableString($locationProperties, 'zipCode'),
            timeZone: DataExtractor::nullableString($locationProperties, 'timeZone'),
            latitude: DataExtractor::nullableFloat($locationProperties, 'latitude'),
            longitude: DataExtractor::nullableFloat($locationProperties, 'longitude'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'manufacturerDeviceId' => $this->manufacturerDeviceId,
            'model' => $this->model,
            'firmware' => $this->firmware,
            'parentDeviceGid' => $this->parentDeviceGid,
            'parentChannelNum' => $this->parentChannelNum,
            'isConnected' => $this->isConnected,
            'offlineSince' => $this->offlineSince?->format(DateTimeImmutable::ATOM),
            'channels' => $this->channels,
            'deviceName' => $this->deviceName,
            'displayName' => $this->displayName,
            'zipCode' => $this->zipCode,
            'timeZone' => $this->timeZone,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
