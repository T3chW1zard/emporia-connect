<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Support;

use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\Contracts\TokenProviderContract;

final class StaticTokenProvider implements TokenProviderContract
{
    public int $refreshes = 0;

    public function __construct(private string $idToken = 'id-token-1') {}

    public function tokens(): TokenSet
    {
        return new TokenSet($this->idToken);
    }

    public function refresh(): TokenSet
    {
        $this->refreshes++;
        $this->idToken = 'id-token-'.($this->refreshes + 1);

        return new TokenSet($this->idToken);
    }
}
