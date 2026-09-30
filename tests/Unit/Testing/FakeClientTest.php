<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Testing;

use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Testing\FakeClient;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class FakeClientTest extends TestCase
{
    public function test_implements_client_contract_with_realistic_defaults(): void
    {
        $fake = new FakeClient;

        $this->assertInstanceOf(ClientContract::class, $fake);
        $this->assertSame(1234, $fake->customers()->me()->customerGid);
        $this->assertCount(4, $fake->devices()->all());
        $this->assertCount(3, $fake->channels()->types());
        $this->assertCount(1, $fake->outlets()->all());
        $this->assertCount(1, $fake->chargers()->all());
        $this->assertCount(1, $fake->vehicles()->all());
        $this->assertArrayHasKey(2345, $fake->usage()->devices(2345));
        $this->assertCount(4, $fake->usage()->chart(2345, '1')->usage);
        $this->assertNull($fake->downForMaintenance());
    }

    public function test_responses_can_be_overridden(): void
    {
        $fake = new FakeClient([
            'GET customers' => ['customerGid' => 99, 'email' => 'override@example.com'],
            'GET customers/vehicles' => static fn (): array => [],
        ]);

        $this->assertSame(99, $fake->customers()->me()->customerGid);
        $this->assertSame([], $fake->vehicles()->all());
    }

    public function test_records_requests(): void
    {
        $fake = new FakeClient;
        $fake->outlets()->turnOn($fake->outlets()->all()[0]);

        $this->assertTrue($fake->transporter()->hasSent('PUT', 'devices/outlet'));
        $this->assertFalse($fake->transporter()->hasSent('PUT', 'devices/evcharger'));
        $this->assertSame(['deviceGid' => 3456, 'outletOn' => true, 'loadGid' => 5678], $fake->transporter()->sentTo('put', 'devices/outlet')[0]['payload']);
        $this->assertCount(2, $fake->transporter()->sent());
    }

    public function test_unknown_routes_return_empty_responses(): void
    {
        $fake = new FakeClient;

        $this->assertSame([], $fake->transporter()->get('unknown/endpoint'));
    }

    public function test_maintenance_message(): void
    {
        $fake = new FakeClient(maintenanceMessage: 'down');
        $this->assertSame('down', $fake->downForMaintenance());

        $fake->setMaintenanceMessage(null);
        $this->assertNull($fake->downForMaintenance());
    }
}
