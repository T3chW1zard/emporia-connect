<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Testing;

/**
 * Realistic Emporia API response bodies, taken from PyEmVue's API documentation and simulator.
 */
final class Fixtures
{
    /** @return array<string, mixed> */
    public static function customer(): array
    {
        return [
            'customerGid' => 1234,
            'email' => 'you@example.com',
            'firstName' => 'First',
            'lastName' => 'Last',
            'createdAt' => '2020-01-01T12:34:56.789Z',
        ];
    }

    /** @return array<string, mixed> */
    public static function devices(): array
    {
        return self::customer() + [
            'devices' => [
                [
                    'deviceGid' => 2345,
                    'manufacturerDeviceId' => 'A2107A04B8F7C1D0',
                    'model' => 'VUE002',
                    'firmware' => 'Vue2-1639421154',
                    'parentDeviceGid' => null,
                    'parentChannelNum' => null,
                    'locationProperties' => self::locationProperties(2345, 'Home'),
                    'outlet' => null,
                    'evCharger' => null,
                    'deviceConnected' => ['deviceGid' => 2345, 'connected' => true, 'offlineSince' => null],
                    'devices' => [
                        [
                            'deviceGid' => 2346,
                            'manufacturerDeviceId' => 'A2107A04B8F7C1D1',
                            'model' => 'VUE002',
                            'firmware' => 'Vue2-1639421154',
                            'channels' => [
                                ['deviceGid' => 2346, 'name' => 'Garage', 'channelNum' => '1,2,3', 'channelMultiplier' => 1.0, 'channelTypeGid' => null, 'type' => 'Main'],
                            ],
                        ],
                    ],
                    'channels' => [
                        ['deviceGid' => 2345, 'name' => null, 'channelNum' => '1,2,3', 'channelMultiplier' => 1.0, 'channelTypeGid' => null, 'type' => 'Main'],
                        ['deviceGid' => 2345, 'name' => 'Kitchen', 'channelNum' => '1', 'channelMultiplier' => 1.0, 'channelTypeGid' => 11, 'type' => 'FiftyAmp'],
                        ['deviceGid' => 2345, 'name' => 'A/C', 'channelNum' => '2', 'channelMultiplier' => 2.0, 'channelTypeGid' => 1, 'type' => 'FiftyAmp'],
                    ],
                ],
                [
                    'deviceGid' => 3456,
                    'manufacturerDeviceId' => 'B3207C05D9E8F2A1',
                    'model' => 'SSO001',
                    'firmware' => 'Outlet-1594685591',
                    'parentDeviceGid' => 2345,
                    'parentChannelNum' => '1,2,3',
                    'locationProperties' => self::locationProperties(3456, 'Plug'),
                    'outlet' => self::outlet(),
                    'evCharger' => null,
                    'deviceConnected' => ['deviceGid' => 3456, 'connected' => false, 'offlineSince' => '2024-05-01T10:00:00Z'],
                    'devices' => [],
                    'channels' => [
                        ['deviceGid' => 3456, 'name' => null, 'channelNum' => '1,2,3', 'channelMultiplier' => 1.0, 'channelTypeGid' => 23, 'type' => 'Main'],
                    ],
                ],
                [
                    'deviceGid' => 4567,
                    'manufacturerDeviceId' => 'C4307D06EAF9A3B2',
                    'model' => 'EVC001',
                    'firmware' => 'EVSE-1680000000',
                    'parentDeviceGid' => null,
                    'parentChannelNum' => null,
                    'locationProperties' => self::locationProperties(4567, 'Charger'),
                    'outlet' => null,
                    'evCharger' => self::charger(),
                    'deviceConnected' => ['deviceGid' => 4567, 'connected' => true, 'offlineSince' => null],
                    'devices' => [],
                    'channels' => [
                        ['deviceGid' => 4567, 'name' => null, 'channelNum' => '1,2,3', 'channelMultiplier' => 1.0, 'channelTypeGid' => null, 'type' => 'Main'],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function locationProperties(int $deviceGid = 2345, string $name = 'Home'): array
    {
        return [
            'deviceGid' => $deviceGid,
            'deviceName' => strtolower($name),
            'displayName' => $name,
            'zipCode' => '12345',
            'timeZone' => 'America/New_York',
            'billingCycleStartDay' => 15,
            'usageCentPerKwHour' => 15.0,
            'peakDemandDollarPerKw' => 0.0,
            'solar' => false,
            'utilityRateGid' => null,
            'locationInformation' => [
                'airConditioning' => 'true',
                'heatSource' => 'naturalGasFurnace',
                'locationSqFt' => '1200',
                'numElectricCars' => '1',
                'locationType' => 'houseMultiLevel',
                'numPeople' => '2',
                'swimmingPool' => 'false',
                'hotTub' => 'false',
            ],
            'latitudeLongitude' => ['latitude' => 40.7128, 'longitude' => -74.006],
        ];
    }

    /** @return array<string, mixed> */
    public static function outlet(bool $on = false): array
    {
        return ['deviceGid' => 3456, 'outletOn' => $on, 'loadGid' => 5678, 'schedules' => [], 'parentDeviceGid' => 2345, 'parentChannelNum' => '1,2,3'];
    }

    /** @return array<string, mixed> */
    public static function charger(bool $on = true, int $rate = 25): array
    {
        return [
            'deviceGid' => 4567,
            'loadGid' => 6789,
            'message' => 'Check your EV',
            'status' => 'Standby',
            'icon' => 'CarConnected',
            'iconLabel' => 'Ready',
            'iconDetailText' => 'Your charger is ready, but your EV is not.',
            'faultText' => null,
            'chargerOn' => $on,
            'chargingRate' => $rate,
            'maxChargingRate' => 40,
            'offPeakSchedulesEnabled' => false,
            'debugCode' => '311',
            'proControlCode' => null,
            'breakerPIN' => null,
        ];
    }

    /** @return array<string, mixed> */
    public static function devicesStatus(): array
    {
        return [
            'devicesConnected' => [
                ['deviceGid' => 2345, 'connected' => true, 'offlineSince' => null],
                ['deviceGid' => 3456, 'connected' => true, 'offlineSince' => null],
                ['deviceGid' => 4567, 'connected' => false, 'offlineSince' => '2024-06-01T08:00:00Z'],
            ],
            'outlets' => [self::outlet()],
            'evChargers' => [self::charger()],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function channelTypes(): array
    {
        return [
            ['channelTypeGid' => 1, 'description' => 'Air Conditioner', 'selectable' => true, 'allowsBidirectional' => false],
            ['channelTypeGid' => 11, 'description' => 'Kitchen', 'selectable' => true, 'allowsBidirectional' => false],
            ['channelTypeGid' => 23, 'description' => 'Solar/Generation', 'selectable' => true, 'allowsBidirectional' => true],
        ];
    }

    /** @return array<string, mixed> */
    public static function deviceListUsages(): array
    {
        return [
            'deviceListUsages' => [
                'instant' => '2024-06-01T12:00:00Z',
                'scale' => '1MIN',
                'energyUnit' => 'KilowattHours',
                'devices' => [
                    [
                        'deviceGid' => 2345,
                        'channelUsages' => [
                            [
                                'name' => 'Main',
                                'usage' => 0.05,
                                'deviceGid' => 2345,
                                'channelNum' => '1,2,3',
                                'percentage' => 100.0,
                                'nestedDevices' => [
                                    [
                                        'deviceGid' => 3456,
                                        'channelUsages' => [
                                            ['name' => 'Plug', 'usage' => 0.005, 'deviceGid' => 3456, 'channelNum' => '1,2,3', 'percentage' => 10.0, 'nestedDevices' => []],
                                        ],
                                    ],
                                ],
                            ],
                            ['name' => 'Kitchen', 'usage' => 0.01, 'deviceGid' => 2345, 'channelNum' => '1', 'percentage' => 20.0, 'nestedDevices' => []],
                            ['name' => 'A/C', 'usage' => 0.0, 'deviceGid' => 2345, 'channelNum' => '2', 'percentage' => 0.0, 'nestedDevices' => []],
                            ['name' => 'Balance', 'usage' => 0.035, 'deviceGid' => 2345, 'channelNum' => 'Balance', 'percentage' => 70.0, 'nestedDevices' => []],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function chartUsage(): array
    {
        return ['firstUsageInstant' => '2024-06-01T00:00:00Z', 'usageList' => [0.5, 0.25, null, 1.0]];
    }

    /** @return list<array<string, mixed>> */
    public static function vehicles(): array
    {
        return [
            ['vehicleGid' => 789, 'vendor' => 'TESLA', 'apiId' => 'api-789', 'displayName' => 'Model 3', 'loadGid' => 6789, 'make' => 'Tesla', 'model' => 'Model 3', 'year' => 2022],
        ];
    }

    /** @return array<string, mixed> */
    public static function vehicleStatus(): array
    {
        return [
            'settings' => [
                'vehicleGid' => 789,
                'vehicleState' => 'Online',
                'batteryLevel' => 80.0,
                'batteryRange' => 250.5,
                'chargingState' => 'Charging',
                'chargeLimitPercent' => 90.0,
                'minutesToFullCharge' => 45,
                'chargeCurrentRequest' => 32.0,
                'chargeCurrentRequestMax' => 48.0,
            ],
        ];
    }
}
