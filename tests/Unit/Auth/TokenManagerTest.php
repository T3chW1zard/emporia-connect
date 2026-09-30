<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Auth;

use GuzzleHttp\Psr7\HttpFactory;
use T3chW1zard\EmporiaConnect\Auth\CognitoClient;
use T3chW1zard\EmporiaConnect\Auth\Stores\InMemoryTokenStore;
use T3chW1zard\EmporiaConnect\Auth\TokenManager;
use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\Enums\AuthFlow;
use T3chW1zard\EmporiaConnect\Exceptions\AuthenticationException;
use T3chW1zard\EmporiaConnect\Tests\Support\FrozenClock;
use T3chW1zard\EmporiaConnect\Tests\Support\Jwt;
use T3chW1zard\EmporiaConnect\Tests\Support\MockHttp;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class TokenManagerTest extends TestCase
{
    private MockHttp $http;

    private FrozenClock $clock;

    private InMemoryTokenStore $store;

    protected function setUp(): void
    {
        $this->http = new MockHttp;
        $this->clock = new FrozenClock;
        $this->store = new InMemoryTokenStore;
    }

    private function manager(?string $username = 'user@example.com', ?string $password = 'secret', ?TokenSet $initial = null): TokenManager
    {
        $factory = new HttpFactory;
        $cognito = new CognitoClient($this->http->client, $factory, $factory, AuthFlow::PASSWORD, clock: $this->clock);

        return new TokenManager($cognito, $this->store, $username, $password, $initial, 60, $this->clock);
    }

    private function jwt(string $modifier): string
    {
        return Jwt::make(['exp' => $this->clock->now()->modify($modifier)->getTimestamp()]);
    }

    public function test_logs_in_when_no_tokens_are_stored_and_persists_them(): void
    {
        $idToken = $this->jwt('+1 hour');
        $this->http->cognitoTokens($idToken);

        $manager = $this->manager();

        $this->assertSame($idToken, $manager->tokens()->idToken);
        $this->assertSame($idToken, $this->store->get()?->idToken);
        $this->assertSame('refresh-token', $this->store->get()?->refreshToken);

        // Cached in memory: no second Cognito call.
        $this->assertSame($idToken, $manager->tokens()->idToken);
        $this->assertSame(1, $this->http->count());
    }

    public function test_uses_stored_tokens_without_calling_cognito(): void
    {
        $idToken = $this->jwt('+30 minutes');
        $this->store->put(TokenSet::fromTokens($idToken, 'access', 'refresh'));

        $this->assertSame($idToken, $this->manager()->tokens()->idToken);
        $this->assertSame(0, $this->http->count());
    }

    public function test_refreshes_expired_tokens_with_the_refresh_token(): void
    {
        $this->store->put(TokenSet::fromTokens($this->jwt('-5 minutes'), 'access', 'stored-refresh'));
        $fresh = $this->jwt('+1 hour');
        $this->http->cognitoTokens($fresh, refreshToken: null);

        $tokens = $this->manager()->tokens();

        $this->assertSame($fresh, $tokens->idToken);
        $this->assertSame('stored-refresh', $tokens->refreshToken);
        $this->assertSame('REFRESH_TOKEN_AUTH', $this->http->body(0)['AuthFlow']);
        $this->assertSame($fresh, $this->store->get()?->idToken);
    }

    public function test_refreshes_shortly_before_expiry(): void
    {
        $this->store->put(TokenSet::fromTokens($this->jwt('+30 seconds'), 'access', 'refresh'));
        $this->http->cognitoTokens($this->jwt('+1 hour'));

        $this->manager()->tokens();

        $this->assertSame(1, $this->http->count());
    }

    public function test_falls_back_to_login_when_refresh_token_is_rejected(): void
    {
        $this->store->put(TokenSet::fromTokens($this->jwt('-1 minute'), 'access', 'revoked'));
        $fresh = $this->jwt('+1 hour');
        $this->http
            ->json(['__type' => 'NotAuthorizedException', 'message' => 'Refresh Token has been revoked'], 400)
            ->cognitoTokens($fresh);

        $this->assertSame($fresh, $this->manager()->tokens()->idToken);
        $this->assertSame('USER_PASSWORD_AUTH', $this->http->body(1)['AuthFlow']);
    }

    public function test_rejected_refresh_without_password_forgets_tokens_and_throws(): void
    {
        $this->store->put(TokenSet::fromTokens($this->jwt('-1 minute'), 'access', 'revoked'));
        $this->http->json(['__type' => 'NotAuthorizedException', 'message' => 'revoked'], 400);

        try {
            $this->manager(null, null)->tokens();
            $this->fail('Expected exception');
        } catch (AuthenticationException) {
            $this->assertNull($this->store->get());
        }
    }

    public function test_force_refresh_after_401(): void
    {
        $this->store->put(TokenSet::fromTokens($this->jwt('+1 hour'), 'access', 'refresh'));
        $fresh = $this->jwt('+2 hours');
        $this->http->cognitoTokens($fresh);

        $manager = $this->manager();
        $manager->tokens();

        $this->assertSame($fresh, $manager->refresh()->idToken);
    }

    public function test_initial_tokens_are_used_when_store_is_empty(): void
    {
        $idToken = $this->jwt('+1 hour');

        $manager = $this->manager(null, null, TokenSet::fromTokens($idToken, 'a', 'r'));

        $this->assertSame($idToken, $manager->tokens()->idToken);
        $this->assertSame(0, $this->http->count());
    }

    public function test_throws_without_tokens_or_credentials(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('no username/password');

        $this->manager(null, null)->tokens();
    }

    public function test_forget_clears_the_store(): void
    {
        $this->store->put(new TokenSet('id'));

        $this->manager()->forget();

        $this->assertNull($this->store->get());
    }
}
