<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Contracts;

use T3chW1zard\EmporiaConnect\Exceptions\EmporiaException;

/**
 * Internal HTTP transport abstraction.
 * Implemented by Transporter (Guzzle-backed) and FakeClient (in-memory).
 */
interface TransporterContract
{
    /**
     * @throws EmporiaException
     * @return array<string, mixed>
     */
    public function get(string $uri): array;

    /**
     * @param  array<string, mixed>  $payload
     * @throws EmporiaException
     * @return array<string, mixed>
     */
    public function put(string $uri, array $payload): array;
}
