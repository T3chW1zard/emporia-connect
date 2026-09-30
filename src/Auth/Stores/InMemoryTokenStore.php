<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Auth\Stores;

use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\Contracts\TokenStoreContract;

/**
 * Keeps tokens for the lifetime of the PHP process only. Used when no cache is configured.
 */
final class InMemoryTokenStore implements TokenStoreContract
{
    public function __construct(private ?TokenSet $tokens = null) {}

    public function get(): ?TokenSet
    {
        return $this->tokens;
    }

    public function put(TokenSet $tokens): void
    {
        $this->tokens = $tokens;
    }

    public function forget(): void
    {
        $this->tokens = null;
    }
}
