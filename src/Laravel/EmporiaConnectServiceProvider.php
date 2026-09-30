<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Laravel;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;
use Psr\SimpleCache\CacheInterface;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\EmporiaConnect;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Registers the Emporia client as a singleton bound to ClientContract (auto-discovered).
 */
final class EmporiaConnectServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom($this->configPath(), 'emporia');

        $this->app->singleton(ClientContract::class, static function (Container $app): ClientContract {
            $repository = $app->make('config');
            $config = $repository instanceof ConfigRepository ? $repository->get('emporia', []) : [];
            $config = is_array($config) ? $config : [];

            $cacheConfig = DataExtractor::array($config, 'cache');
            $cache = null;

            if (DataExtractor::bool($cacheConfig, 'enabled', true) && $app->bound('cache')) {
                $factory = $app->make('cache');
                $store = $factory instanceof CacheFactory ? $factory->store(DataExtractor::nullableString($cacheConfig, 'store')) : $factory;
                $cache = $store instanceof CacheInterface ? $store : null;
            }

            return EmporiaConnect::fromOptions([
                ...$config,
                'cache_key' => DataExtractor::nullableString($cacheConfig, 'key'),
                'cache_ttl' => DataExtractor::nullableInt($cacheConfig, 'ttl'),
            ], $cache);
        });

        $this->app->alias(ClientContract::class, 'emporia');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([$this->configPath() => $this->app->configPath('emporia.php')], 'emporia-config');
        }
    }

    /** @return list<string> */
    public function provides(): array
    {
        return [ClientContract::class, 'emporia'];
    }

    private function configPath(): string
    {
        return dirname(__DIR__, 2).'/config/emporia.php';
    }
}
