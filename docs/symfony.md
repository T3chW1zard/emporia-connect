# Symfony Integration

The package ships `T3chW1zard\EmporiaConnect\Symfony\EmporiaConnectBundle` (Symfony 6.4 and 7.x).

## Installation

Register the bundle:

```php
// config/bundles.php
return [
    // ...
    T3chW1zard\EmporiaConnect\Symfony\EmporiaConnectBundle::class => ['all' => true],
];
```

Add your credentials to `.env.local`:

```dotenv
EMPORIA_USERNAME=you@example.com
EMPORIA_PASSWORD=your-password
```

## Configuration

```yaml
# config/packages/emporia_connect.yaml
emporia_connect:
    username: '%env(EMPORIA_USERNAME)%'
    password: '%env(EMPORIA_PASSWORD)%'

    # Service id of a PSR-6 or PSR-16 cache used to keep the tokens (default: cache.app).
    # Set to ~ to disable caching.
    cache: cache.app
    cache_key: ~            # derived from the username by default
    cache_ttl: 2592000      # seconds

    # Or store the tokens in a JSON file instead of a cache:
    # token_file: '%kernel.project_dir%/var/emporia-tokens.json'

    # Or start from existing tokens:
    # id_token: '%env(EMPORIA_ID_TOKEN)%'
    # access_token: '%env(EMPORIA_ACCESS_TOKEN)%'
    # refresh_token: '%env(EMPORIA_REFRESH_TOKEN)%'

    auth_flow: srp          # srp | password
    http_client: ~          # service id of a PSR-18 client, Guzzle by default
    connect_timeout: 6.03
    read_timeout: 10.03
    token_refresh_leeway: 60
    retry:
        max_attempts: 5
        initial_delay_ms: 500
        max_delay_ms: 30000
```

Symfony cache pools (`cache.app`, or a dedicated pool) implement PSR-6 and work directly. For a
dedicated pool:

```yaml
framework:
    cache:
        pools:
            cache.emporia:
                adapter: cache.adapter.redis

emporia_connect:
    cache: cache.emporia
```

## Usage

Autowire the contract:

```php
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Enums\Scale;

final class EnergyController extends AbstractController
{
    public function __construct(private readonly ClientContract $emporia) {}

    #[Route('/energy/{deviceGid}')]
    public function index(int $deviceGid): JsonResponse
    {
        return new JsonResponse($this->emporia->usage()->chart($deviceGid, '1,2,3', scale: Scale::HOUR));
    }
}
```

The service id is `emporia_connect.client`, aliased to `ClientContract`.

## Without the bundle

You can also register the client with the static factory:

```yaml
services:
    T3chW1zard\EmporiaConnect\Contracts\ClientContract:
        factory: ['T3chW1zard\EmporiaConnect\EmporiaConnect', 'client']
        arguments:
            $username: '%env(EMPORIA_USERNAME)%'
            $password: '%env(EMPORIA_PASSWORD)%'
            $cache: '@cache.app'
```

## Testing

Replace the service in the test environment:

```yaml
# config/services_test.yaml
services:
    T3chW1zard\EmporiaConnect\Contracts\ClientContract:
        class: T3chW1zard\EmporiaConnect\Testing\FakeClient
        public: true
```

`FakeClient` returns realistic fixture data, never touches the network and records every request
(`$fake->transporter()->sent()`).
