<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Represents an available channel type in the Emporia system.
 */
readonly class ChannelTypeResponse
{
    public function __construct(
        public int $channelTypeGid,
        public string $description,
        public bool $selectable,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            channelTypeGid: DataExtractor::int($data, 'channelTypeGid'),
            description: DataExtractor::string($data, 'description'),
            selectable: DataExtractor::bool($data, 'selectable'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'channelTypeGid' => $this->channelTypeGid,
            'description' => $this->description,
            'selectable' => $this->selectable,
        ];
    }
}
