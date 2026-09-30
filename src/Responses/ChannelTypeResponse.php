<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * A channel type (appliance category) that can be assigned to a channel.
 */
final readonly class ChannelTypeResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $channelTypeGid,
        public string $description,
        public bool $selectable,
        public bool $allowsBidirectional = false,
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            channelTypeGid: DataExtractor::int($data, 'channelTypeGid'),
            description: DataExtractor::string($data, 'description'),
            selectable: DataExtractor::bool($data, 'selectable'),
            allowsBidirectional: DataExtractor::bool($data, 'allowsBidirectional'),
        );
    }

    public function toArray(): array
    {
        return [
            'channelTypeGid' => $this->channelTypeGid,
            'description' => $this->description,
            'selectable' => $this->selectable,
            'allowsBidirectional' => $this->allowsBidirectional,
        ];
    }
}
