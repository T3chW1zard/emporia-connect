# t3chw1zard/emporia-connect

A framework-agnostic PHP 8.2+ client for the [Emporia Energy](https://www.emporiaenergy.com/) Vue API:
energy monitors, smart plugs and EV chargers.

It is a full PHP port of [PyEmVue](https://github.com/magico13/PyEmVue): every PyEmVue call is
available, with typed, immutable response objects, token caching, and first-class Laravel and
Symfony integration.

## Features

- Every PyEmVue feature: devices, location properties, device status, channels, channel types,
  instant usage (with nested smart plugs), chart usage, outlets, EV chargers, vehicles and the
  maintenance check
- AWS Cognito login with **SRP** (like the Emporia app and PyEmVue) or plain password auth
- **Token caching** in any PSR-16 or PSR-6 cache (or a JSON file). Tokens are refreshed with the
  refresh token, so the client logs in with your password only when it has to
- Automatic token refresh on expiry or a `401`, and retries with exponential back-off on `5xx`
- Typed, immutable, `JsonSerializable` response classes
- PSR-18 HTTP client (Guzzle by default), PSR-17 factories, PSR-20 clock
- Laravel service provider (auto-discovered), config file and `Emporia` facade with `Emporia::fake()`
- Symfony bundle with configuration tree
- `FakeClient` for your own tests
- PHPStan level 9

## Installation

```bash
composer require t3chw1zard/emporia-connect
```

Installing `ext-gmp` is recommended: it makes the SRP login handshake much faster.

## Quick start

```php
use T3chW1zard\EmporiaConnect\EmporiaConnect;
use T3chW1zard\EmporiaConnect\Enums\Scale;

$client = EmporiaConnect::client('you@example.com', 'your-password', cache: $psr16OrPsr6Cache);

$customer = $client->customers()->me();
$devices  = $client->devices()->all();

// Current usage of every channel on a device (kWh used during the last minute)
$usage = $client->usage()->devices($devices[0]->deviceGid, scale: Scale::MINUTE);

foreach ($usage[$devices[0]->deviceGid]->channels as $channel) {
    printf("%s: %.0f W\n", $channel->name, $channel->watts());
}
```

Creating a client never touches the network. Tokens are fetched from the cache, refreshed, or
obtained by logging in on the first API call.

## Authentication and token caching

The Emporia API authenticates with AWS Cognito tokens. The id token is valid for one hour; the
refresh token for 30 days. Pass a cache and the client stores the token set there, so later
requests (and later PHP processes) reuse or refresh it instead of logging in again.

```php
// PSR-16 (Laravel cache repository, Symfony Psr16Cache, ...) or PSR-6 (Symfony cache pools, ...)
$client = EmporiaConnect::client('you@example.com', 'secret', cache: $cache);

// A JSON token file, like PyEmVue's token_storage_file
$client = EmporiaConnect::builder()
    ->withCredentials('you@example.com', 'secret')
    ->withTokenFile(__DIR__.'/storage/emporia-tokens.json')
    ->build();

// Existing tokens, no password
$client = EmporiaConnect::fromTokens($idToken, $accessToken, $refreshToken, cache: $cache);

// Your own storage, e.g. a database table
$client = EmporiaConnect::client('you@example.com', 'secret', cache: new MyTokenStore()); // implements TokenStoreContract
```

The cache entry contains a refresh token, so treat the cache as sensitive storage.

### Full configuration

```php
use T3chW1zard\EmporiaConnect\Enums\AuthFlow;
use T3chW1zard\EmporiaConnect\Http\RetryPolicy;

$client = EmporiaConnect::builder()
    ->withCredentials('you@example.com', 'secret')
    ->withCache($cache, key: 'emporia.tokens', ttl: 2592000)
    ->withAuthFlow(AuthFlow::SRP)            // or AuthFlow::PASSWORD
    ->withTimeouts(connectTimeout: 6.03, readTimeout: 10.03)
    ->withRetryPolicy(new RetryPolicy(maxAttempts: 5, initialDelayMs: 500, maxDelayMs: 30000))
    ->withTokenRefreshLeeway(60)             // refresh this many seconds before expiry
    ->withHttpClient($psr18Client)           // optional, Guzzle by default
    ->build();
```

## API reference

Each PyEmVue method maps to a method in this package:

| PyEmVue | emporia-connect |
|---|---|
| `login(...)` | `EmporiaConnect::client()` / `fromTokens()` / `builder()` |
| `down_for_maintenance()` | `$client->downForMaintenance()` |
| `get_customer_details()` | `$client->customers()->me()` |
| `get_devices()` | `$client->devices()->all()` |
| — | `$client->devices()->find($gid)` |
| `populate_device_properties(device)` | `$client->devices()->populateLocationProperties($device)` / `locationProperties($gid)` |
| `get_devices_status(device_list)` | `$client->devices()->status()` / `withConnectionStatus($devices)` |
| `update_channel(channel)` | `$client->channels()->update($channel->withName('Oven'))` |
| — | `$client->channels()->all($gid)` / `find($gid, $channelNum)` |
| `get_channel_types()` | `$client->channels()->types()` |
| `get_device_list_usage(gids, instant, scale, unit)` | `$client->usage()->devices($gids, $instant, $scale, $unit)` |
| `get_chart_usage(channel, start, end, scale, unit)` | `$client->usage()->chart($gid, $channelNum, $start, $end, $scale, $unit)` / `chartForChannel($channel, ...)` |
| `get_outlets()` | `$client->outlets()->all()` |
| `update_outlet(outlet, on)` | `$client->outlets()->update($outlet, on: true)` / `turnOn()` / `turnOff()` |
| `get_chargers()` | `$client->chargers()->all()` |
| `update_charger(charger, on, charge_rate)` | `$client->chargers()->update($charger, on: true, chargeRate: 32)` / `turnOn()` / `turnOff()` |
| `get_vehicles()` | `$client->vehicles()->all()` |
| `get_vehicle_status(gid)` | `$client->vehicles()->status($gid)` |

### Devices

```php
$devices = $client->devices()->all();              // list<DeviceResponse>; sub-devices are flattened like PyEmVue

$device = $devices[0];
$device->deviceGid;                                // 12345
$device->model;                                    // "VUE002"
$device->channels;                                 // list<DeviceChannelResponse>
$device->outlet;                                   // ?OutletResponse (smart plugs)
$device->evCharger;                                // ?ChargerResponse (EV chargers)
$device->locationProperties?->timeZone;            // "America/New_York"
$device->name();                                   // display name from the app

$status  = $client->devices()->status();           // outlets, chargers and devicesConnected
$devices = $client->devices()->withConnectionStatus($devices);
```

### Usage

`usage` values are the energy used during one scale period in the requested unit (kWh by default).
`watts()` converts kWh to average power.

```php
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Enums\Unit;

// Instant usage of one or more devices, including nested smart plugs / sub-panels
$usage = $client->usage()->devices([12345, 67890], scale: Scale::MINUTE, unit: Unit::KILOWATT_HOURS);

$mains = $usage[12345]->channel('1,2,3');
$mains->usage;                                     // 0.05 (kWh in the last minute)
$mains->watts();                                   // 3000.0
$mains->percentage;                                // 100.0
$mains->nestedDevices;                             // array<int, UsageDeviceResponse>

// Historical usage of one channel
$chart = $client->usage()->chart(12345, '1,2,3', start: '-1 day', scale: Scale::HOUR);
$chart->usage;                                     // list<float|null>
$chart->points();                                  // list<array{time: DateTimeImmutable, usage: float|null}>
$chart->watts();
$chart->total();
```

Channel `1,2,3` is the mains and `Balance` is the unmonitored remainder. Like PyEmVue,
`usage()->devices()` retries (with back-off) while the API still returns `null` for recent data.

### Outlets and chargers

```php
$outlet = $client->outlets()->all()[0];
$client->outlets()->turnOff($outlet);

$charger = $client->chargers()->all()[0];
$client->chargers()->update($charger, on: true, chargeRate: 32);   // amps
```

### Channels

```php
$channel = $client->channels()->find(12345, '1');
$client->channels()->update($channel->withName('Kitchen')->withChannelMultiplier(2.0));

$types = $client->channels()->types();             // list<ChannelTypeResponse>
```

### Vehicles

```php
$vehicle = $client->vehicles()->all()[0];
$status  = $client->vehicles()->status($vehicle->vehicleGid);
$status?->batteryLevel;
```

### Responses

All responses are `final readonly` classes implementing `ResponseContract` (`toArray()` and
`JsonSerializable`), so you can return them straight from a controller:

```php
return response()->json($client->devices()->all());   // Laravel
return new JsonResponse($client->devices()->all());   // Symfony
```

### Errors

All exceptions extend `T3chW1zard\EmporiaConnect\Exceptions\EmporiaException`:

| Exception | When |
|---|---|
| `AuthenticationException` | Cognito login/refresh failed (wrong password, MFA required, ...) |
| `ApiException` | The API answered with an error status (`$e->statusCode`, `$e->responseBody`) |
| `TransportException` | The request could not be sent (DNS, connection, timeout) |

## Laravel

The service provider and the `Emporia` facade are auto-discovered. Add your credentials to `.env`:

```dotenv
EMPORIA_USERNAME=you@example.com
EMPORIA_PASSWORD=your-password
```

Tokens are cached in your default cache store. Publish the config to change this:

```bash
php artisan vendor:publish --tag=emporia-config
```

Inject the contract, or use the facade:

```php
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Laravel\Facades\Emporia;

public function index(ClientContract $emporia)
{
    return $emporia->devices()->all();
}

Emporia::usage()->devices(12345);
```

See [docs/laravel.md](docs/laravel.md) for configuration and testing.

## Symfony

Register the bundle and configure it:

```php
// config/bundles.php
return [
    T3chW1zard\EmporiaConnect\Symfony\EmporiaConnectBundle::class => ['all' => true],
];
```

```yaml
# config/packages/emporia_connect.yaml
emporia_connect:
    username: '%env(EMPORIA_USERNAME)%'
    password: '%env(EMPORIA_PASSWORD)%'
    cache: cache.app
```

Then autowire `T3chW1zard\EmporiaConnect\Contracts\ClientContract`. See [docs/symfony.md](docs/symfony.md).

## Testing your application

Type-hint `ClientContract` and swap in the `FakeClient`, which returns realistic fixture data and
records every request:

```php
use T3chW1zard\EmporiaConnect\Testing\FakeClient;

$fake = new FakeClient([
    'GET customers' => ['customerGid' => 1, 'email' => 'test@example.com'],
    'GET AppAPI?apiMethod=getChartUsage*' => ['firstUsageInstant' => '2024-01-01T00:00:00Z', 'usageList' => [1.0, 2.0]],
]);

$fake->outlets()->turnOn($fake->outlets()->all()[0]);

$fake->transporter()->hasSent('PUT', 'devices/outlet');   // true
```

## Development

```bash
composer test        # PHPUnit
composer analyse     # PHPStan level 9
composer format      # Laravel Pint
composer qa          # everything
```

## Credits

- [PyEmVue](https://github.com/magico13/PyEmVue) by magico13, whose API research this package is built on
- [pycognito](https://github.com/NabuCasa/pycognito) for the Cognito SRP implementation

## License

MIT
