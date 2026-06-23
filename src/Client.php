<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect;

use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Resources\Channels;
use T3chW1zard\EmporiaConnect\Resources\Chargers;
use T3chW1zard\EmporiaConnect\Resources\Customers;
use T3chW1zard\EmporiaConnect\Resources\Devices;
use T3chW1zard\EmporiaConnect\Resources\Outlets;

/**
 * Authenticated Emporia Vue API client.
 *
 * @see ClientContract
 */
final readonly class Client implements ClientContract
{
    public function __construct(private TransporterContract $transporter) {}

    public function customers(): Customers
    {
        return new Customers($this->transporter);
    }

    public function devices(): Devices
    {
        return new Devices($this->transporter);
    }

    public function channels(): Channels
    {
        return new Channels($this->transporter);
    }

    public function outlets(): Outlets
    {
        return new Outlets($this->transporter);
    }

    public function chargers(): Chargers
    {
        return new Chargers($this->transporter);
    }
}
