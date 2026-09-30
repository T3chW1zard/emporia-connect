<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses\Concerns;

/**
 * Implements JsonSerializable on top of toArray().
 */
trait SerializesToJson
{
    /** @return array<string, mixed> */
    abstract public function toArray(): array;

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
