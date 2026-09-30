<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Contracts;

use T3chW1zard\EmporiaConnect\Auth\TokenSet;

/**
 * Persists Cognito tokens between requests/processes so the client does not have to
 * log in every time.
 */
interface TokenStoreContract
{
    public function get(): ?TokenSet;

    public function put(TokenSet $tokens): void;

    public function forget(): void;
}
