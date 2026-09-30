<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Contracts\TokenStoreContract;

/**
 * Entry point for creating an Emporia Connect client.
 *
 *   $client = EmporiaConnect::client('user@example.com', 'secret', cache: $cache);
 *   $devices = $client->devices()->all();
 */
final class EmporiaConnect
{
    /**
     * Client that logs in with username and password.
     *
     * Pass a PSR-16/PSR-6 cache (or a token store) to keep tokens between requests, so the
     * client only logs in again when the refresh token is no longer valid.
     */
    public static function client(
        string $username,
        #[\SensitiveParameter]
        string $password,
        CacheInterface|CacheItemPoolInterface|TokenStoreContract|null $cache = null,
        ?ClientInterface $httpClient = null,
    ): ClientContract {
        return self::configure(self::builder()->withCredentials($username, $password), $cache, $httpClient)->build();
    }

    /**
     * Client that starts from previously obtained tokens.
     */
    public static function fromTokens(
        string $idToken,
        ?string $accessToken = null,
        ?string $refreshToken = null,
        CacheInterface|CacheItemPoolInterface|TokenStoreContract|null $cache = null,
        ?ClientInterface $httpClient = null,
    ): ClientContract {
        return self::configure(self::builder()->withTokens($idToken, $accessToken, $refreshToken), $cache, $httpClient)->build();
    }

    /**
     * Client built from a configuration array; see {@see ClientBuilder::fromOptions()} for the keys.
     *
     * @param  array<array-key, mixed>  $options
     */
    public static function fromOptions(
        array $options,
        CacheInterface|CacheItemPoolInterface|null $cache = null,
        ?ClientInterface $httpClient = null,
    ): ClientContract {
        return ClientBuilder::fromOptions($options, $cache, $httpClient)->build();
    }

    public static function builder(): ClientBuilder
    {
        return new ClientBuilder;
    }

    private static function configure(
        ClientBuilder $builder,
        CacheInterface|CacheItemPoolInterface|TokenStoreContract|null $cache,
        ?ClientInterface $httpClient,
    ): ClientBuilder {
        $builder = match (true) {
            $cache instanceof TokenStoreContract => $builder->withTokenStore($cache),
            $cache !== null => $builder->withCache($cache),
            default => $builder,
        };

        return $httpClient instanceof ClientInterface ? $builder->withHttpClient($httpClient) : $builder;
    }
}
