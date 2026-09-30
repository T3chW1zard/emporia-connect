<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Auth\Stores;

use Psr\Cache\CacheItemPoolInterface;
use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\Contracts\TokenStoreContract;

/**
 * Stores tokens in any PSR-6 cache pool (e.g. Symfony's "cache.app").
 */
final readonly class Psr6TokenStore implements TokenStoreContract
{
    public function __construct(
        private CacheItemPoolInterface $pool,
        private string $key = 'emporia_connect.tokens',
        private ?int $ttl = 2592000,
    ) {}

    public function get(): ?TokenSet
    {
        $item = $this->pool->getItem($this->key);
        $value = $item->isHit() ? $item->get() : null;

        return is_array($value) ? TokenSet::fromArray($value) : null;
    }

    public function put(TokenSet $tokens): void
    {
        $item = $this->pool->getItem($this->key);
        $item->set($tokens->toArray());
        $item->expiresAfter($this->ttl);

        $this->pool->save($item);
    }

    public function forget(): void
    {
        $this->pool->deleteItem($this->key);
    }
}
