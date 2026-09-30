# Laravel Integration

The package ships an auto-discovered service provider
(`T3chW1zard\EmporiaConnect\Laravel\EmporiaConnectServiceProvider`) and an `Emporia` facade.
Laravel 11 and 12 are supported.

## Configuration

Add your Emporia account to `.env`:

```dotenv
EMPORIA_USERNAME=you@example.com
EMPORIA_PASSWORD=your-password
```

That's all you need. To change the defaults, publish the config file:

```bash
php artisan vendor:publish --tag=emporia-config
```

`config/emporia.php`:

| Key | Env | Default | Description |
|---|---|---|---|
| `username` | `EMPORIA_USERNAME` | — | Account email |
| `password` | `EMPORIA_PASSWORD` | — | Account password |
| `id_token` / `access_token` / `refresh_token` | `EMPORIA_ID_TOKEN`, ... | — | Start from existing tokens instead of a password |
| `cache.enabled` | `EMPORIA_CACHE_ENABLED` | `true` | Cache tokens between requests |
| `cache.store` | `EMPORIA_CACHE_STORE` | default store | Cache store name, e.g. `redis` |
| `cache.key` | `EMPORIA_CACHE_KEY` | derived from username | Cache key |
| `cache.ttl` | `EMPORIA_CACHE_TTL` | `2592000` | Seconds (refresh tokens are valid for 30 days) |
| `auth_flow` | `EMPORIA_AUTH_FLOW` | `srp` | `srp` or `password` |
| `connect_timeout` / `read_timeout` | | `6.03` / `10.03` | Seconds |
| `token_refresh_leeway` | | `60` | Refresh the id token this many seconds before it expires |
| `retry.*` | | 5 attempts, 500 ms → 30 s | Back-off for `5xx` responses |

## Token caching

With caching enabled (the default), the Cognito tokens, including the refresh token, are stored in
your cache store:

1. The first request logs in and stores the tokens.
2. Later requests (including queue workers and other servers sharing the cache) reuse them.
3. When the id token expires it is refreshed with the refresh token. The password is only used
   again if the refresh token has been revoked or has expired.

Use a persistent, shared store (Redis, database, ...) in production. The `array` store only lives
for a single request.

## Usage

Inject the contract:

```php
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Enums\Scale;

class EnergyController extends Controller
{
    public function __construct(private ClientContract $emporia) {}

    public function index()
    {
        $device = $this->emporia->devices()->all()[0];

        return response()->json([
            'device' => $device,
            'usage' => $this->emporia->usage()->devices($device->deviceGid, scale: Scale::MINUTE),
        ]);
    }
}
```

Or use the facade:

```php
use T3chW1zard\EmporiaConnect\Laravel\Facades\Emporia;

$chart = Emporia::usage()->chart(12345, '1,2,3', start: now()->subDay(), scale: Scale::HOUR);
```

Response objects implement `JsonSerializable`, so they can be returned from controllers directly.

## Scheduling

A typical setup polls usage from the scheduler and stores it:

```php
// routes/console.php
Schedule::call(function (ClientContract $emporia) {
    foreach ($emporia->usage()->devices([12345], scale: Scale::MINUTE) as $device) {
        foreach ($device->channels as $channel) {
            Reading::create([
                'device_gid' => $device->deviceGid,
                'channel' => $channel->channelNum,
                'watts' => $channel->watts(),
                'measured_at' => $device->timestamp,
            ]);
        }
    }
})->everyMinute();
```

## Testing

`Emporia::fake()` swaps the client for an in-memory `FakeClient`, both for the facade and for
anything that injects `ClientContract`:

```php
use T3chW1zard\EmporiaConnect\Laravel\Facades\Emporia;

public function test_dashboard_shows_usage(): void
{
    $fake = Emporia::fake([
        'GET customers' => ['customerGid' => 1, 'email' => 'test@example.com', 'firstName' => 'Test'],
    ]);

    $this->get('/dashboard')->assertOk()->assertSee('Test');

    $this->assertTrue($fake->transporter()->hasSent('GET', 'customers'));
}
```

Without the facade, bind the fake yourself:

```php
$this->app->instance(ClientContract::class, new FakeClient());
```
