<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Testing;

use Closure;
use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use T3chW1zard\EmporiaConnect\Client;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Resources\Channels;
use T3chW1zard\EmporiaConnect\Resources\Chargers;
use T3chW1zard\EmporiaConnect\Resources\Customers;
use T3chW1zard\EmporiaConnect\Resources\Devices;
use T3chW1zard\EmporiaConnect\Resources\Outlets;
use T3chW1zard\EmporiaConnect\Resources\Usage;
use T3chW1zard\EmporiaConnect\Resources\Vehicles;

/**
 * Drop-in replacement for the real client in your tests. Never touches the network.
 *
 *   $fake = new FakeClient(['GET customers' => ['customerGid' => 1, 'email' => 'me@example.com']]);
 *   $fake->customers()->me();
 *   $fake->transporter()->hasSent('GET', 'customers'); // true
 */
final class FakeClient implements ClientContract
{
    private readonly FakeTransporter $transporter;

    private readonly Client $client;

    /**
     * @param  array<string, array<array-key, mixed>|Closure(string, array<string, mixed>|null): array<array-key, mixed>>  $responses  route overrides, see {@see FakeTransporter}
     */
    public function __construct(
        array $responses = [],
        private ?string $maintenanceMessage = null,
        ?ClockInterface $clock = null,
    ) {
        $this->transporter = new FakeTransporter($responses);
        $this->client = new Client($this->transporter, clock: $clock ?? $this->frozenClock());
    }

    public function transporter(): FakeTransporter
    {
        return $this->transporter;
    }

    public function customers(): Customers
    {
        return $this->client->customers();
    }

    public function devices(): Devices
    {
        return $this->client->devices();
    }

    public function channels(): Channels
    {
        return $this->client->channels();
    }

    public function usage(): Usage
    {
        return $this->client->usage();
    }

    public function outlets(): Outlets
    {
        return $this->client->outlets();
    }

    public function chargers(): Chargers
    {
        return $this->client->chargers();
    }

    public function vehicles(): Vehicles
    {
        return $this->client->vehicles();
    }

    public function downForMaintenance(): ?string
    {
        return $this->maintenanceMessage;
    }

    public function setMaintenanceMessage(?string $message): void
    {
        $this->maintenanceMessage = $message;
    }

    private function frozenClock(): ClockInterface
    {
        return new class implements ClockInterface
        {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2024-06-01T12:00:00Z', new DateTimeZone('UTC'));
            }
        };
    }
}
