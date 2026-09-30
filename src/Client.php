<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect;

use Psr\Clock\ClockInterface;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Http\MaintenanceChecker;
use T3chW1zard\EmporiaConnect\Resources\Channels;
use T3chW1zard\EmporiaConnect\Resources\Chargers;
use T3chW1zard\EmporiaConnect\Resources\Customers;
use T3chW1zard\EmporiaConnect\Resources\Devices;
use T3chW1zard\EmporiaConnect\Resources\Outlets;
use T3chW1zard\EmporiaConnect\Resources\Usage;
use T3chW1zard\EmporiaConnect\Resources\Vehicles;
use T3chW1zard\EmporiaConnect\Support\SystemClock;

/**
 * Emporia Vue API client. Build one with {@see EmporiaConnect}.
 */
final readonly class Client implements ClientContract
{
    public function __construct(
        private TransporterContract $transporter,
        private ?MaintenanceChecker $maintenance = null,
        private ClockInterface $clock = new SystemClock,
    ) {}

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

    public function usage(): Usage
    {
        return new Usage($this->transporter, $this->clock);
    }

    public function outlets(): Outlets
    {
        return new Outlets($this->transporter);
    }

    public function chargers(): Chargers
    {
        return new Chargers($this->transporter);
    }

    public function vehicles(): Vehicles
    {
        return new Vehicles($this->transporter);
    }

    public function downForMaintenance(): ?string
    {
        return $this->maintenance?->check();
    }
}
