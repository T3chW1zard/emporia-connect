# Laravel Integration

No ServiceProvider is bundled. Wire the client in your `AppServiceProvider`:

```php
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\EmporiaConnect;

public function register(): void
{
    $this->app->singleton(ClientContract::class, fn () => EmporiaConnect::client(
        username: config('services.emporia.username'),
        password: config('services.emporia.password'),
    ));
}
```

Add to `config/services.php`:

```php
'emporia' => [
    'username' => env('EMPORIA_USERNAME'),
    'password' => env('EMPORIA_PASSWORD'),
],
```

## Token Caching (Recommended)

Pass Laravel's cache store to avoid re-authenticating on every request:

```php
use Illuminate\Support\Facades\Cache;

$this->app->singleton(ClientContract::class, fn () => EmporiaConnect::client(
    username: config('services.emporia.username'),
    password: config('services.emporia.password'),
    cache: Cache::store(),
));
```

Laravel's `Illuminate\Cache\Repository` implements PSR-16 `CacheInterface`, so it works directly.

## Usage

```php
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Enums\Scale;

class EnergyController extends Controller
{
    public function __construct(private ClientContract $emporia) {}

    public function index(): JsonResponse
    {
        $usage = $this->emporia->channels()->usage(
            deviceGid: 12345,
            channelNum: '1',
            scale: Scale::HOUR,
        );

        return response()->json($usage->toArray());
    }
}
```

## Testing

Swap the real client with the in-memory fake in your feature tests:

```php
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Testing\FakeClient;

protected function setUp(): void
{
    parent::setUp();
    $this->app->bind(ClientContract::class, FakeClient::class);
}
```

`FakeClient` returns predictable fixture data and never makes HTTP calls.
