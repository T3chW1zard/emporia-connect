<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Resources\Outlets;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class OutletsTest extends TestCase
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

    public function test_all_returns_outlet_array(): void
    {
        $outlets = (new Outlets($this->makeTransporter([
            'outlets' => [['deviceGid' => 1, 'outlet' => ['outletOn' => true, 'loadGid' => null, 'schedules' => []]]],
        ])))->all();

        $this->assertCount(1, $outlets);
        $this->assertTrue($outlets[0]->outletOn);
    }

    public function test_update_sends_put_and_returns_outlet(): void
    {
        $outlet = (new Outlets($this->makeTransporter(putReturn: [
            'deviceGid' => 1, 'outlet' => ['outletOn' => false, 'loadGid' => null, 'schedules' => []],
        ])))->update(1, false);

        $this->assertFalse($outlet->outletOn);
    }
}
