<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Contracts;

use T3chW1zard\EmporiaConnect\Exceptions\EmporiaException;
use T3chW1zard\EmporiaConnect\Http\Transporter;
use T3chW1zard\EmporiaConnect\Testing\FakeTransporter;

/**
 * Authenticated HTTP transport to the Emporia API.
 *
 * Implemented by {@see Transporter} (PSR-18 backed) and
 * {@see FakeTransporter} (in-memory).
 */
interface TransporterContract
{
    /**
     * Send a GET request and return the decoded JSON body ([] for an empty body).
     *
     * @param  string  $uri  path relative to the API root, e.g. "customers/devices"
     * @return array<array-key, mixed>
     *
     * @throws EmporiaException
     */
    public function get(string $uri): array;

    /**
     * Send a PUT request with a JSON body and return the decoded JSON body ([] for an empty body).
     *
     * @param  array<string, mixed>  $payload
     * @return array<array-key, mixed>
     *
     * @throws EmporiaException
     */
    public function put(string $uri, array $payload): array;
}
