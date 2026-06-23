<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit;

use T3chW1zard\EmporiaConnect\Client;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Resources\Channels;
use T3chW1zard\EmporiaConnect\Resources\Chargers;
use T3chW1zard\EmporiaConnect\Resources\Customers;
use T3chW1zard\EmporiaConnect\Resources\Devices;
use T3chW1zard\EmporiaConnect\Resources\Outlets;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        $transporter = new class implements TransporterContract {
            public function get(string $uri): array
            {
                return [];
            }

            public function put(string $uri, array $payload): array
            {
                return [];
            }
        };
        $this->client = new Client($transporter);
    }

    public function test_customers_returns_customers_resource(): void
    {
        $this->assertInstanceOf(Customers::class, $this->client->customers());
    }

    public function test_devices_returns_devices_resource(): void
    {
        $this->assertInstanceOf(Devices::class, $this->client->devices());
    }

    public function test_channels_returns_channels_resource(): void
    {
        $this->assertInstanceOf(Channels::class, $this->client->channels());
    }

    public function test_outlets_returns_outlets_resource(): void
    {
        $this->assertInstanceOf(Outlets::class, $this->client->outlets());
    }

    public function test_chargers_returns_chargers_resource(): void
    {
        $this->assertInstanceOf(Chargers::class, $this->client->chargers());
    }
}
