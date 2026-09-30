<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Contracts;

use JsonSerializable;

/**
 * Immutable, typed API response object.
 */
interface ResponseContract extends JsonSerializable
{
    /** @return array<string, mixed> */
    public function toArray(): array;
}
