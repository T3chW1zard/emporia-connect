<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * A measuring channel of a device.
 *
 * The channel number is used by the usage endpoints; "1,2,3" is the mains, "Balance" the unmonitored remainder.
 */
final readonly class DeviceChannelResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $deviceGid,
        public ?string $name,
        public string $channelNum,
        public float $channelMultiplier = 1.0,
        public ?int $channelTypeGid = null,
        /** Known types: Main, FiftyAmp, FiftyAmpBidirectional. */
        public ?string $type = null,
        public ?string $parentChannelNum = null,
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            name: DataExtractor::nullableString($data, 'name'),
            channelNum: DataExtractor::string($data, 'channelNum', '1,2,3'),
            channelMultiplier: DataExtractor::float($data, 'channelMultiplier', 1.0),
            channelTypeGid: DataExtractor::nullableInt($data, 'channelTypeGid'),
            type: DataExtractor::nullableString($data, 'type'),
            parentChannelNum: DataExtractor::nullableString($data, 'parentChannelNum'),
        );
    }

    public function withName(?string $name): self
    {
        return new self($this->deviceGid, $name, $this->channelNum, $this->channelMultiplier, $this->channelTypeGid, $this->type, $this->parentChannelNum);
    }

    public function withChannelMultiplier(float $channelMultiplier): self
    {
        return new self($this->deviceGid, $this->name, $this->channelNum, $channelMultiplier, $this->channelTypeGid, $this->type, $this->parentChannelNum);
    }

    public function withChannelTypeGid(?int $channelTypeGid): self
    {
        return new self($this->deviceGid, $this->name, $this->channelNum, $this->channelMultiplier, $channelTypeGid, $this->type, $this->parentChannelNum);
    }

    /**
     * Request body for updating the channel.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'name' => $this->name,
            'channelNum' => $this->channelNum,
            'channelMultiplier' => $this->channelMultiplier,
            'channelTypeGid' => $this->channelTypeGid,
            'type' => $this->type,
            'parentChannelNum' => $this->parentChannelNum,
        ];
    }
}
