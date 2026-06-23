# Symfony Integration

Wire the client via `config/services.yaml`:

```yaml
T3chW1zard\EmporiaConnect\Contracts\ClientContract:
    factory: ['T3chW1zard\EmporiaConnect\EmporiaConnect', 'client']
    arguments:
        $username: '%env(EMPORIA_USERNAME)%'
        $password: '%env(EMPORIA_PASSWORD)%'
```

Add to your `.env`:

```dotenv
EMPORIA_USERNAME=user@example.com
EMPORIA_PASSWORD=your-password
```

## Token Caching (Recommended)

Symfony's `cache.app` pool implements PSR-16 via an adapter. Pass it as the third argument:

```yaml
T3chW1zard\EmporiaConnect\Contracts\ClientContract:
    factory: ['T3chW1zard\EmporiaConnect\EmporiaConnect', 'client']
    arguments:
        $username: '%env(EMPORIA_USERNAME)%'
        $password: '%env(EMPORIA_PASSWORD)%'
        $cache: '@cache.app.simple'
```

`cache.app.simple` is the PSR-16 alias exposed by Symfony's FrameworkBundle. Alternatively, use `Symfony\Component\Cache\Psr16Cache` wrapping any PSR-6 pool.

## Usage

```php
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use Symfony\Component\HttpFoundation\JsonResponse;

class EnergyController extends AbstractController
{
    public function __construct(private ClientContract $emporia) {}

    #[Route('/energy')]
    public function index(): JsonResponse
    {
        $usage = $this->emporia->channels()->usage(
            deviceGid: 12345,
            channelNum: '1',
            scale: Scale::HOUR,
        );

        return new JsonResponse($usage->toArray());
    }
}
```

## Testing

Override the binding in `config/packages/test/services.yaml`:

```yaml
T3chW1zard\EmporiaConnect\Contracts\ClientContract:
    class: T3chW1zard\EmporiaConnect\Testing\FakeClient
```

`FakeClient` returns predictable fixture data and never makes HTTP calls.
