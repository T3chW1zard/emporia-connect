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
     * @return array<string, mixed>
     *
     * @throws EmporiaException
     */
    public function get(string $uri): array;

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws EmporiaException
     */
    public function put(string $uri, array $payload): array;
}
