<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Resources\Chargers;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class ChargersTest extends TestCase
{
    private function makeTransporter(array $getReturn = [], array $putReturn = []): TransporterContract
    {
        return new class($getReturn, $putReturn) implements TransporterContract
        {
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

    public function test_all_returns_charger_array(): void
    {
        $chargers = (new Chargers($this->makeTransporter([
            'evChargers' => [['deviceGid' => 5, 'evCharger' => ['chargerOn' => true, 'offPeakSchedulesEnabled' => false]]],
        ])))->all();

        $this->assertCount(1, $chargers);
        $this->assertTrue($chargers[0]->chargerOn);
    }

    public function test_update_returns_charger_response(): void
    {
        $charger = (new Chargers($this->makeTransporter(putReturn: [
            'deviceGid' => 5, 'evCharger' => ['chargerOn' => false, 'offPeakSchedulesEnabled' => false],
        ])))->update(5, false, 7.2);

        $this->assertFalse($charger->chargerOn);
    }
}
