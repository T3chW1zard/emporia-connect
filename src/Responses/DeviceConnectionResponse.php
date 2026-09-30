<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use DateTimeImmutable;
use DateTimeInterface;
use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Online status of a device ("deviceConnected" / "devicesConnected" entries).
 */
final readonly class DeviceConnectionResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $deviceGid,
        public bool $connected,
        public ?DateTimeImmutable $offlineSince = null,
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            connected: DataExtractor::bool($data, 'connected'),
            offlineSince: DataExtractor::nullableDate($data, 'offlineSince'),
        );
    }

    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'connected' => $this->connected,
            'offlineSince' => $this->offlineSince?->format(DateTimeInterface::ATOM),
        ];
    }
}
