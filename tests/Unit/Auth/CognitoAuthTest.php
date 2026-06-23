<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Auth;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Psr\SimpleCache\CacheInterface;
use T3chW1zard\EmporiaConnect\Auth\CognitoAuth;
use T3chW1zard\EmporiaConnect\Exceptions\AuthenticationException;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class CognitoAuthTest extends TestCase
{
    private function makeAuth(MockHandler $mock, ?CacheInterface $cache = null): CognitoAuth
    {
        $client = new Client(['handler' => HandlerStack::create($mock)]);

        return new CognitoAuth(
            client: $client,
            cognitoUrl: 'https://cognito-idp.us-east-2.amazonaws.com/',
            clientId: '4qte47jbstod8apnfic0bunmrq',
            cache: $cache,
        );
    }

    public function test_returns_id_token_on_success(): void
    {
        $mock = new MockHandler([
            new Response(200, [], json_encode(['AuthenticationResult' => ['IdToken' => 'fake-id-token', 'ExpiresIn' => 3600]])),
        ]);

        $token = $this->makeAuth($mock)->authenticate('user@example.com', 'secret');

        $this->assertSame('fake-id-token', $token);
    }

    public function test_throws_on_missing_authentication_result(): void
    {
        $mock = new MockHandler([new Response(200, [], json_encode([]))]);

        $this->expectException(AuthenticationException::class);
        $this->makeAuth($mock)->authenticate('user@example.com', 'secret');
    }

    public function test_throws_on_http_error(): void
    {
        $mock = new MockHandler([new Response(400, [], json_encode(['message' => 'Bad request']))]);

        $this->expectException(AuthenticationException::class);
        $this->makeAuth($mock)->authenticate('user@example.com', 'wrong');
    }

    public function test_uses_cache_to_avoid_repeat_calls(): void
    {
        $cache = new class implements CacheInterface
        {
            public array $store = [];

            public function get($key, $default = null): mixed
            {
                return $this->store[$key] ?? $default;
            }

            public function set($key, $value, $ttl = null): bool
            {
                $this->store[$key] = $value;

                return true;
            }

            public function delete($key): bool
            {
                unset($this->store[$key]);

                return true;
            }

            public function clear(): bool
            {
                $this->store = [];

                return true;
            }

            public function getMultiple($keys, $default = null): iterable
            {
                return [];
            }

            public function setMultiple($values, $ttl = null): bool
            {
                return true;
            }

            public function deleteMultiple($keys): bool
            {
                return true;
            }

            public function has($key): bool
            {
                return isset($this->store[$key]);
            }
        };

        $mock = new MockHandler([
            new Response(200, [], json_encode(['AuthenticationResult' => ['IdToken' => 'fake-id-token', 'ExpiresIn' => 3600]])),
        ]);

        $auth = $this->makeAuth($mock, $cache);
        $token1 = $auth->authenticate('user@example.com', 'secret');
        $token2 = $auth->authenticate('user@example.com', 'secret');

        $this->assertSame('fake-id-token', $token1);
        $this->assertSame('fake-id-token', $token2);
        // MockHandler count decrements on each use; after 1 call, remaining = 0
        $this->assertCount(0, $mock);
    }
}
