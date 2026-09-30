<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Auth\Stores;

use Psr\SimpleCache\CacheInterface;
use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\Contracts\TokenStoreContract;

/**
 * Stores tokens in any PSR-16 cache (Laravel's Cache repository, Symfony's Psr16Cache, ...).
 *
 * The entry holds the refresh token too, so it outlives the one hour id token and lets the
 * client refresh silently instead of logging in again.
 */
final readonly class Psr16TokenStore implements TokenStoreContract
{
    public function __construct(
        private CacheInterface $cache,
        private string $key = 'emporia_connect.tokens',
        private ?int $ttl = 2592000,
    ) {}

    public function get(): ?TokenSet
    {
        $value = $this->cache->get($this->key);

        return is_array($value) ? TokenSet::fromArray($value) : null;
    }

    public function put(TokenSet $tokens): void
    {
        $this->cache->set($this->key, $tokens->toArray(), $this->ttl);
    }

    public function forget(): void
    {
        $this->cache->delete($this->key);
    }
}
