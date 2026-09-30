<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Auth;

use DateTimeImmutable;
use DateTimeInterface;
use JsonSerializable;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * The set of AWS Cognito tokens for an Emporia account.
 *
 * The id token is what the Emporia API expects in the "authtoken" header; the refresh
 * token is used to obtain new id/access tokens without the password.
 */
final readonly class TokenSet implements JsonSerializable
{
    public function __construct(
        public string $idToken,
        public ?string $accessToken = null,
        public ?string $refreshToken = null,
        /** Unix timestamp at which the id token expires, or null when unknown. */
        public ?int $expiresAt = null,
    ) {}

    /**
     * Build a token set from supplied token strings. The expiry is read from the id token itself.
     */
    public static function fromTokens(string $idToken, ?string $accessToken = null, ?string $refreshToken = null): self
    {
        return new self(
            idToken: $idToken,
            accessToken: self::blankToNull($accessToken),
            refreshToken: self::blankToNull($refreshToken),
            expiresAt: self::jwtExpiry($idToken),
        );
    }

    /**
     * Build a token set from a Cognito "AuthenticationResult" payload.
     *
     * A refresh response does not contain a new refresh token, so the previous one is kept.
     *
     * @param  array<array-key, mixed>  $result
     */
    public static function fromCognito(array $result, DateTimeInterface $now, ?string $previousRefreshToken = null): ?self
    {
        $idToken = DataExtractor::string($result, 'IdToken');

        if ($idToken === '') {
            return null;
        }

        $expiresIn = DataExtractor::nullableInt($result, 'ExpiresIn');

        return new self(
            idToken: $idToken,
            accessToken: self::blankToNull(DataExtractor::nullableString($result, 'AccessToken')),
            refreshToken: self::blankToNull(DataExtractor::nullableString($result, 'RefreshToken')) ?? $previousRefreshToken,
            expiresAt: self::jwtExpiry($idToken) ?? ($expiresIn !== null ? $now->getTimestamp() + $expiresIn : null),
        );
    }

    /**
     * Rebuild a token set from {@see self::toArray()} output (e.g. a cache entry or token file).
     *
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $idToken = DataExtractor::string($data, 'id_token');

        if ($idToken === '') {
            return null;
        }

        return new self(
            idToken: $idToken,
            accessToken: self::blankToNull(DataExtractor::nullableString($data, 'access_token')),
            refreshToken: self::blankToNull(DataExtractor::nullableString($data, 'refresh_token')),
            expiresAt: DataExtractor::nullableInt($data, 'expires_at') ?? self::jwtExpiry($idToken),
        );
    }

    /**
     * Whether the id token is expired (or will be within $leewaySeconds). Unknown expiry counts as valid.
     */
    public function isExpired(DateTimeInterface $now, int $leewaySeconds = 60): bool
    {
        return $this->expiresAt !== null && $now->getTimestamp() + $leewaySeconds >= $this->expiresAt;
    }

    public function canRefresh(): bool
    {
        return $this->refreshToken !== null;
    }

    public function expiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt === null ? null : (new DateTimeImmutable)->setTimestamp($this->expiresAt);
    }

    /**
     * Array form used by the token stores.
     *
     * @return array{id_token: string, access_token: string|null, refresh_token: string|null, expires_at: int|null}
     */
    public function toArray(): array
    {
        return [
            'id_token' => $this->idToken,
            'access_token' => $this->accessToken,
            'refresh_token' => $this->refreshToken,
            'expires_at' => $this->expiresAt,
        ];
    }

    /** @return array{id_token: string, access_token: string|null, refresh_token: string|null, expires_at: int|null} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Read the "exp" claim of a JWT without verifying it (the API verifies it for us).
     */
    public static function jwtExpiry(string $jwt): ?int
    {
        $parts = explode('.', $jwt);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);

        if ($payload === false) {
            return null;
        }

        $claims = json_decode($payload, true);

        return is_array($claims) ? DataExtractor::nullableInt($claims, 'exp') : null;
    }

    private static function blankToNull(?string $value): ?string
    {
        return $value === null || $value === '' ? null : $value;
    }
}
