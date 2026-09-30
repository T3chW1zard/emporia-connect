<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Auth;

use Psr\Clock\ClockInterface;
use T3chW1zard\EmporiaConnect\Auth\Stores\InMemoryTokenStore;
use T3chW1zard\EmporiaConnect\Contracts\TokenProviderContract;
use T3chW1zard\EmporiaConnect\Contracts\TokenStoreContract;
use T3chW1zard\EmporiaConnect\Exceptions\AuthenticationException;
use T3chW1zard\EmporiaConnect\Support\SystemClock;

/**
 * Keeps a valid token set available, mirroring PyEmVue's Auth class:
 *
 *  1. use tokens from the store (cache) when present,
 *  2. refresh them with the refresh token when the id token expired,
 *  3. fall back to a full username/password login,
 *
 * and persist every new token set back to the store.
 */
final class TokenManager implements TokenProviderContract
{
    private ?TokenSet $current = null;

    private readonly ClockInterface $clock;

    public function __construct(
        private readonly CognitoClient $cognito,
        private readonly TokenStoreContract $store = new InMemoryTokenStore,
        private readonly ?string $username = null,
        #[\SensitiveParameter]
        private readonly ?string $password = null,
        private readonly ?TokenSet $initialTokens = null,
        private readonly int $leewaySeconds = 60,
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new SystemClock;
    }

    public function tokens(): TokenSet
    {
        $this->current ??= $this->store->get() ?? $this->initialTokens;

        if (! $this->current instanceof TokenSet) {
            return $this->login();
        }

        if ($this->current->isExpired($this->clock->now(), $this->leewaySeconds)) {
            return $this->refresh();
        }

        return $this->current;
    }

    public function refresh(): TokenSet
    {
        $this->current ??= $this->store->get() ?? $this->initialTokens;
        $refreshToken = $this->current?->refreshToken;

        if ($refreshToken !== null) {
            try {
                return $this->remember($this->cognito->refresh($refreshToken));
            } catch (AuthenticationException $e) {
                // The refresh token was revoked or expired: forget it and log in again if we can.
                $this->forget();

                if (! $this->canLogin()) {
                    throw $e;
                }
            }
        }

        return $this->login();
    }

    /**
     * Drop the current tokens from memory and from the store.
     */
    public function forget(): void
    {
        $this->current = null;
        $this->store->forget();
    }

    private function login(): TokenSet
    {
        if (! $this->canLogin()) {
            throw new AuthenticationException('No valid tokens available and no username/password configured to log in.');
        }

        return $this->remember($this->cognito->authenticate((string) $this->username, (string) $this->password));
    }

    private function canLogin(): bool
    {
        return $this->username !== null && $this->username !== '' && $this->password !== null && $this->password !== '';
    }

    private function remember(TokenSet $tokens): TokenSet
    {
        $this->store->put($tokens);

        return $this->current = $tokens;
    }
}
