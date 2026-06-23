<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\SimpleCache\CacheInterface;
use T3chW1zard\EmporiaConnect\Exceptions\AuthenticationException;

/**
 * Handles AWS Cognito USER_PASSWORD_AUTH flow for Emporia Vue.
 * Caches the IdToken via PSR-16 when a cache implementation is provided.
 */
final readonly class CognitoAuth
{
    public function __construct(
        private Client $client,
        private string $cognitoUrl,
        private string $clientId,
        private ?CacheInterface $cache = null,
    ) {}

    /**
     * @throws AuthenticationException
     */
    public function authenticate(string $username, string $password): string
    {
        $cacheKey = $this->cacheKey($username);

        if ($this->cache instanceof CacheInterface) {
            $cached = $this->cache->get($cacheKey);
            if (is_string($cached) && $cached !== '') {
                return $cached;
            }
        }

        try {
            $response = $this->client->post($this->cognitoUrl, [
                'headers' => [
                    'X-Amz-Target' => 'AWSCognitoIdentityProviderService.InitiateAuth',
                    'Content-Type' => 'application/x-amz-json-1.1',
                ],
                'json' => [
                    'AuthParameters' => ['USERNAME' => $username, 'PASSWORD' => $password],
                    'AuthFlow' => 'USER_PASSWORD_AUTH',
                    'ClientId' => $this->clientId,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new AuthenticationException('HTTP request to Cognito failed: '.$e->getMessage(), $e->getCode(), previous: $e);
        }

        if ($response->getStatusCode() !== 200) {
            throw new AuthenticationException(
                'Cognito authentication failed with status '.$response->getStatusCode(),
            );
        }

        /** @var array<string, mixed>|null $body */
        $body = json_decode((string) $response->getBody(), true);
        /** @var array<string, mixed>|null $auth */
        $auth = is_array($body) ? ($body['AuthenticationResult'] ?? null) : null;

        if ($auth === null || ! isset($auth['IdToken'])) {
            throw new AuthenticationException('Authentication failed: No IdToken in response.');
        }

        $idToken = $auth['IdToken'];
        $token = is_string($idToken) ? $idToken : '';

        if ($token === '') {
            throw new AuthenticationException('Authentication failed: IdToken is empty.');
        }

        $expiresRaw = $auth['ExpiresIn'] ?? null;
        $expiresIn = max(1, is_int($expiresRaw) ? $expiresRaw - 10 : 3540);

        $this->cache?->set($cacheKey, $token, $expiresIn);

        return $token;
    }

    private function cacheKey(string $username): string
    {
        return 'emporia_token_'.md5($username.$this->clientId);
    }
}
