<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Integration\Laravel;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use T3chW1zard\EmporiaConnect\Client;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Laravel\EmporiaConnectServiceProvider;
use T3chW1zard\EmporiaConnect\Laravel\Facades\Emporia;
use T3chW1zard\EmporiaConnect\Testing\FakeClient;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class EmporiaConnectServiceProviderTest extends TestCase
{
    private Container $app;

    protected function setUp(): void
    {
        $this->app = new class extends Container
        {
            public bool $console = true;

            public function runningInConsole(): bool
            {
                return $this->console;
            }

            public function configPath(string $path = ''): string
            {
                return '/app/config/'.$path;
            }
        };

        Container::setInstance($this->app);
        $this->app->instance('config', new Repository([
            'emporia' => ['username' => 'user@example.com', 'password' => 'secret'],
        ]));

        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance();
    }

    private function register(): EmporiaConnectServiceProvider
    {
        $provider = new EmporiaConnectServiceProvider($this->app);
        $provider->register();
        $provider->boot();

        return $provider;
    }

    public function test_it_merges_default_config(): void
    {
        $this->register();

        $config = $this->app->make('config');
        $this->assertSame('user@example.com', $config->get('emporia.username'));
        $this->assertSame('srp', $config->get('emporia.auth_flow'));
        $this->assertSame(5, $config->get('emporia.retry.max_attempts'));
    }

    public function test_it_binds_a_singleton_client(): void
    {
        $this->register();

        $client = $this->app->make(ClientContract::class);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame($client, $this->app->make(ClientContract::class));
        $this->assertSame($client, $this->app->make('emporia'));
    }

    public function test_it_uses_the_configured_cache_store(): void
    {
        $factory = new class(new Psr16Cache(new ArrayAdapter)) implements CacheFactory
        {
            /** @var list<string|null> */
            public array $requested = [];

            public function __construct(private readonly CacheInterface $cache) {}

            public function store($name = null): CacheInterface
            {
                $this->requested[] = $name;

                return $this->cache;
            }
        };
        $this->app->instance('cache', $factory);
        $this->app->make('config')->set('emporia.cache.store', 'redis');

        $this->register();
        $this->app->make(ClientContract::class);

        $this->assertSame(['redis'], $factory->requested);
    }

    public function test_cache_can_be_disabled(): void
    {
        $this->app->instance('cache', new class implements CacheFactory
        {
            public function store($name = null): never
            {
                throw new \LogicException('cache must not be used');
            }
        });
        $this->app->make('config')->set('emporia.cache', ['enabled' => false]);

        $this->register();

        $this->assertInstanceOf(ClientContract::class, $this->app->make(ClientContract::class));
    }

    public function test_it_is_deferred_and_publishes_config(): void
    {
        $provider = $this->register();

        $this->assertSame([ClientContract::class, 'emporia'], $provider->provides());
        $this->assertTrue($provider->isDeferred());

        $published = ServiceProvider::pathsToPublish(EmporiaConnectServiceProvider::class, 'emporia-config');
        $this->assertSame(['/app/config/emporia.php'], array_values($published));
        $this->assertFileExists((string) array_key_first($published));
    }

    public function test_facade_resolves_client_and_can_be_faked(): void
    {
        $this->register();

        $fake = Emporia::fake(['GET customers' => ['customerGid' => 42]]);

        $this->assertInstanceOf(FakeClient::class, $fake);
        $this->assertSame(42, Emporia::customers()->me()->customerGid);
        $this->assertSame($fake, $this->app->make(ClientContract::class));
        $this->assertTrue($fake->transporter()->hasSent('GET', 'customers'));
    }

    public function test_composer_registers_provider_and_facade_for_auto_discovery(): void
    {
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/composer.json'), true);

        $this->assertSame([EmporiaConnectServiceProvider::class], $composer['extra']['laravel']['providers']);
        $this->assertSame(Emporia::class, $composer['extra']['laravel']['aliases']['Emporia']);
    }
}
