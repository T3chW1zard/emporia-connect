<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Feature;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use T3chW1zard\EmporiaConnect\Client;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;
use T3chW1zard\EmporiaConnect\Exceptions\EmporiaException;
use T3chW1zard\EmporiaConnect\Tests\TestCase;
use T3chW1zard\EmporiaConnect\Transporter;

final class EmporiaConnectFeatureTest extends TestCase
{
    private MockHandler $mock;

    protected function setUp(): void
    {
        $this->mock = new MockHandler;
    }

    private function makeClient(string $token = 'test-token'): Client
    {
        $guzzle = new GuzzleClient(['handler' => HandlerStack::create($this->mock)]);
        $transporter = new Transporter($guzzle, $token);

        return new Client($transporter);
    }

    private function mockJson(array $data, int $status = 200): void
    {
        $this->mock->append(new Response($status, ['Content-Type' => 'application/json'], json_encode($data)));
    }

    public function test_get_customer_full_round_trip(): void
    {
        $this->mockJson(['customerGid' => 99, 'email' => 'test@test.com', 'firstName' => 'Test', 'lastName' => 'User', 'createdAt' => '2024-01-01T00:00:00Z']);

        $customer = $this->makeClient()->customers()->me();

        $this->assertSame(99, $customer->customerGid);
        $this->assertSame('test@test.com', $customer->email);
    }

    public function test_get_devices_full_round_trip(): void
    {
        $this->mockJson(['devices' => [[
            'deviceGid' => 42, 'manufacturerDeviceId' => 'D1', 'model' => 'Vue002',
            'firmware' => '1.7', 'parentDeviceGid' => null, 'parentChannelNum' => null,
            'deviceConnected' => ['connected' => true, 'offlineSince' => null],
            'devices' => [],
        ]]]);

        $devices = $this->makeClient()->devices()->all();

        $this->assertCount(1, $devices);
        $this->assertSame(42, $devices[0]->deviceGid);
    }

    public function test_get_channel_usage_full_round_trip(): void
    {
        $this->mockJson(['firstUsageInstant' => '2024-06-01T00:00:00Z', 'usageList' => [0.001, 0.002, 0.003]]);

        $usage = $this->makeClient()->channels()->usage(42, '1', Scale::HOUR, Unit::KILOWATT_HOURS);

        $this->assertSame('1', $usage->channelNum);
        $this->assertCount(3, $usage->usage);
        $this->assertArrayHasKey('time', $usage->usage[0]);
    }

    public function test_transporter_throws_on_guzzle_error(): void
    {
        $this->mock->append(new ConnectException('Connection refused', new Request('GET', 'test')));

        $this->expectException(EmporiaException::class);
        $this->makeClient()->customers()->me();
    }

    public function test_outlet_toggle_round_trip(): void
    {
        $this->mockJson(['outlets' => [['deviceGid' => 10, 'outlet' => ['outletOn' => false, 'loadGid' => null, 'schedules' => []]]], 'evChargers' => []]);
        $this->mockJson(['deviceGid' => 10, 'outlet' => ['outletOn' => true, 'loadGid' => null, 'schedules' => []]]);

        $client = $this->makeClient();
        $outlets = $client->outlets()->all();
        $this->assertFalse($outlets[0]->outletOn);

        $updated = $client->outlets()->update(10, true);
        $this->assertTrue($updated->outletOn);
    }
}
