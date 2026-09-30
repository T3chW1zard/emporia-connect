<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Contracts;

use T3chW1zard\EmporiaConnect\Exceptions\EmporiaException;
use T3chW1zard\EmporiaConnect\Resources\Channels;
use T3chW1zard\EmporiaConnect\Resources\Chargers;
use T3chW1zard\EmporiaConnect\Resources\Customers;
use T3chW1zard\EmporiaConnect\Resources\Devices;
use T3chW1zard\EmporiaConnect\Resources\Outlets;
use T3chW1zard\EmporiaConnect\Resources\Usage;
use T3chW1zard\EmporiaConnect\Resources\Vehicles;

/**
 * Public contract for the Emporia Connect client.
 * Type-hint this interface in your application so it can be swapped for the FakeClient in tests.
 */
interface ClientContract
{
    public function customers(): Customers;

    public function devices(): Devices;

    public function channels(): Channels;

    public function usage(): Usage;

    public function outlets(): Outlets;

    public function chargers(): Chargers;

    public function vehicles(): Vehicles;

    /**
     * Maintenance message when the Emporia API is down for maintenance, otherwise null.
     *
     * @throws EmporiaException
     */
    public function downForMaintenance(): ?string;
}
