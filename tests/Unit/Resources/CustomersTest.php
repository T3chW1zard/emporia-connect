<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Resources;

use T3chW1zard\EmporiaConnect\Responses\VehicleStatusResponse;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Resources\Customers;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class CustomersTest extends TestCase
{
    private function makeTransporter(array $getReturn = [], array $putReturn = []): TransporterContract
    {
        return new class ($getReturn, $putReturn) implements TransporterContract {
            public function __construct(private readonly array $getReturn, private readonly array $putReturn) {}

            public function get(string $uri): array
            {
                return $this->getReturn;
            }

            public function put(string $uri, array $payload): array
            {
                return $this->putReturn;
            }
        };
    }

    public function test_me_returns_customer_response(): void
    {
        $transporter = $this->makeTransporter([
            'customerGid' => 1, 'email' => 'a@b.com',
            'firstName' => 'A', 'lastName' => 'B', 'createdAt' => '2024-01-01T00:00:00Z',
        ]);

        $customer = (new Customers($transporter))->me();

        $this->assertSame(1, $customer->customerGid);
        $this->assertSame('a@b.com', $customer->email);
    }

    public function test_vehicles_returns_array(): void
    {
        $transporter = $this->makeTransporter(['vehicles' => [
            ['vehicleGid' => 1, 'vendor' => 'tesla', 'displayName' => 'T', 'make' => 'Tesla', 'model' => '3', 'year' => 2022],
        ]]);

        $vehicles = (new Customers($transporter))->vehicles();

        $this->assertCount(1, $vehicles);
        $this->assertSame(1, $vehicles[0]->vehicleGid);
    }

    public function test_vehicle_status_returns_null_on_empty(): void
    {
        $this->assertNotInstanceOf(VehicleStatusResponse::class, (new Customers($this->makeTransporter([])))->vehicleStatus(1));
    }
}
