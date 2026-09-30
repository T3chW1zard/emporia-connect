<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Laravel\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Resources\Channels;
use T3chW1zard\EmporiaConnect\Resources\Chargers;
use T3chW1zard\EmporiaConnect\Resources\Customers;
use T3chW1zard\EmporiaConnect\Resources\Devices;
use T3chW1zard\EmporiaConnect\Resources\Outlets;
use T3chW1zard\EmporiaConnect\Resources\Usage;
use T3chW1zard\EmporiaConnect\Resources\Vehicles;
use T3chW1zard\EmporiaConnect\Testing\FakeClient;

/**
 * @method static Customers customers()
 * @method static Devices devices()
 * @method static Channels channels()
 * @method static Usage usage()
 * @method static Outlets outlets()
 * @method static Chargers chargers()
 * @method static Vehicles vehicles()
 * @method static string|null downForMaintenance()
 *
 * @see ClientContract
 */
final class Emporia extends Facade
{
    /**
     * Replace the client with an in-memory fake for tests.
     *
     * @param  array<string, array<array-key, mixed>|Closure(string, array<string, mixed>|null): array<array-key, mixed>>  $responses
     */
    public static function fake(array $responses = []): FakeClient
    {
        $fake = new FakeClient($responses);

        self::swap($fake);

        return $fake;
    }

    protected static function getFacadeAccessor(): string
    {
        return ClientContract::class;
    }
}
