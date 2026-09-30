<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Auth;

use DateTimeImmutable;
use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\Tests\Support\Jwt;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class TokenSetTest extends TestCase
{
    public function test_from_tokens_reads_expiry_from_jwt(): void
    {
        $tokens = TokenSet::fromTokens(Jwt::make(['exp' => 1_700_000_000]), 'access', 'refresh');

        $this->assertSame(1_700_000_000, $tokens->expiresAt);
        $this->assertSame('access', $tokens->accessToken);
        $this->assertTrue($tokens->canRefresh());
        $this->assertSame(1_700_000_000, $tokens->expiresAt()?->getTimestamp());
    }

    public function test_non_jwt_token_has_unknown_expiry_and_never_expires(): void
    {
        $tokens = TokenSet::fromTokens('opaque', '', '');

        $this->assertNull($tokens->expiresAt);
        $this->assertNull($tokens->accessToken);
        $this->assertFalse($tokens->canRefresh());
        $this->assertFalse($tokens->isExpired(new DateTimeImmutable('+10 years')));
    }

    public function test_is_expired_honours_leeway(): void
    {
        $tokens = new TokenSet('id', expiresAt: 1000);

        $this->assertFalse($tokens->isExpired((new DateTimeImmutable)->setTimestamp(900), 60));
        $this->assertTrue($tokens->isExpired((new DateTimeImmutable)->setTimestamp(950), 60));
        $this->assertTrue($tokens->isExpired((new DateTimeImmutable)->setTimestamp(1000), 0));
    }

    public function test_from_cognito_falls_back_to_expires_in_and_keeps_previous_refresh_token(): void
    {
        $now = (new DateTimeImmutable)->setTimestamp(5000);

        $tokens = TokenSet::fromCognito(['IdToken' => 'opaque', 'AccessToken' => 'a', 'ExpiresIn' => 3600], $now, 'old-refresh');

        $this->assertInstanceOf(TokenSet::class, $tokens);
        $this->assertSame(8600, $tokens->expiresAt);
        $this->assertSame('old-refresh', $tokens->refreshToken);
    }

    public function test_from_cognito_prefers_new_refresh_token(): void
    {
        $tokens = TokenSet::fromCognito(['IdToken' => 'x', 'RefreshToken' => 'new'], new DateTimeImmutable, 'old');

        $this->assertSame('new', $tokens?->refreshToken);
    }

    public function test_from_cognito_without_id_token_returns_null(): void
    {
        $this->assertNull(TokenSet::fromCognito([], new DateTimeImmutable));
    }

    public function test_array_round_trip_uses_snake_case_keys(): void
    {
        $tokens = new TokenSet('id', 'access', 'refresh', 1234);

        $this->assertSame(['id_token' => 'id', 'access_token' => 'access', 'refresh_token' => 'refresh', 'expires_at' => 1234], $tokens->toArray());
        $this->assertEquals($tokens, TokenSet::fromArray($tokens->toArray()));
        $this->assertSame(json_encode($tokens->toArray()), json_encode($tokens));
        $this->assertNull(TokenSet::fromArray(['access_token' => 'x']));
    }

    public function test_jwt_expiry_handles_garbage(): void
    {
        $this->assertNull(TokenSet::jwtExpiry('a.b'));
        $this->assertNull(TokenSet::jwtExpiry('a.!!!.c'));
        $this->assertNull(TokenSet::jwtExpiry('a.'.base64_encode('not json').'.c'));
    }
}
