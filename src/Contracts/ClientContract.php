<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Contracts;

use T3chW1zard\EmporiaConnect\Resources\Channels;
use T3chW1zard\EmporiaConnect\Resources\Chargers;
use T3chW1zard\EmporiaConnect\Resources\Customers;
use T3chW1zard\EmporiaConnect\Resources\Devices;
use T3chW1zard\EmporiaConnect\Resources\Outlets;

/**
 * Public contract for the Emporia Connect client.
 * Implement this interface to swap or mock the client in consuming applications.
 */
interface ClientContract
{
    public function customers(): Customers;

    public function devices(): Devices;

    public function channels(): Channels;

    public function outlets(): Outlets;

    public function chargers(): Chargers;
}
