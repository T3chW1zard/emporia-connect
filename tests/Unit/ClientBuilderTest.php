<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit;

use InvalidArgumentException;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use T3chW1zard\EmporiaConnect\Auth\CognitoClient;
use T3chW1zard\EmporiaConnect\Auth\Stores\InMemoryTokenStore;
use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\ClientBuilder;
use T3chW1zard\EmporiaConnect\EmporiaConnect;
use T3chW1zard\EmporiaConnect\Enums\AuthFlow;
use T3chW1zard\EmporiaConnect\Tests\Support\FrozenClock;
use T3chW1zard\EmporiaConnect\Tests\Support\Jwt;
use T3chW1zard\EmporiaConnect\Tests\Support\MockHttp;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class ClientBuilderTest extends TestCase
{
    public function test_building_does_not_touch_the_network(): void
    {
        $http = new MockHttp;

        EmporiaConnect::client('user@example.com', 'secret', httpClient: $http->client);

        $this->assertSame(0, $http->count());
    }

    public function test_builder_is_immutable(): void
    {
        $builder = EmporiaConnect::builder();

        $this->assertNotSame($builder, $builder->withCredentials('a', 'b'));
        $this->assertNotSame($builder, $builder->withTimeouts(1, 2));
    }

    public function test_from_tokens_uses_the_given_id_token(): void
    {
        $http = (new MockHttp)->json(['customerGid' => 1]);
        $idToken = Jwt::make(['exp' => time() + 3600]);

        EmporiaConnect::fromTokens($idToken, 'access', 'refresh', httpClient: $http->client)->customers()->me();

        $this->assertSame($idToken, $http->request(0)->getHeaderLine('authtoken'));
    }

    public function test_token_store_can_be_passed_as_cache_argument(): void
    {
        $store = new InMemoryTokenStore(new TokenSet('stored-token'));
        $http = (new MockHttp)->json(['customerGid' => 1]);

        EmporiaConnect::client('user@example.com', 'secret', $store, $http->client)->customers()->me();

        $this->assertSame('stored-token', $http->request(0)->getHeaderLine('authtoken'));
    }

    public function test_cache_key_is_derived_from_the_lowercased_username(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter);
        $http = (new MockHttp)->cognitoTokens('id-token')->json(['customerGid' => 1]);

        EmporiaConnect::builder()
            ->withCredentials('  User@Example.com ', 'secret')
            ->withAuthFlow(AuthFlow::PASSWORD)
            ->withCache($cache)
            ->withHttpClient($http->client)
            ->build()
            ->customers()
            ->me();

        $key = ClientBuilder::DEFAULT_CACHE_KEY.'.'.hash('sha256', 'user@example.com|'.CognitoClient::CLIENT_ID);
        $this->assertIsArray($cache->get($key));
        $this->assertSame('user@example.com', $http->body(0)['AuthParameters']['USERNAME']);
    }

    public function test_from_options_reads_framework_configuration(): void
    {
        $http = (new MockHttp)->json([], 500)->json(['customerGid' => 7]);
        $cache = new ArrayAdapter;

        $client = EmporiaConnect::fromOptions([
            'username' => 'user@example.com',
            'password' => 'secret',
            'id_token' => 'configured-token',
            'auth_flow' => 'password',
            'cache_key' => 'custom-key',
            'cache_ttl' => 60,
            'connect_timeout' => 1.0,
            'read_timeout' => 2.0,
            'token_refresh_leeway' => 30,
            'retry' => ['max_attempts' => 2, 'initial_delay_ms' => 0, 'max_delay_ms' => 0],
        ], $cache, $http->client);

        $this->assertSame(7, $client->customers()->me()->customerGid);
        $this->assertSame(2, $http->count());
        $this->assertSame('configured-token', $http->request(1)->getHeaderLine('authtoken'));
    }

    public function test_from_options_rejects_unknown_auth_flow(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ClientBuilder::fromOptions(['auth_flow' => 'magic']);
    }

    public function test_from_options_with_token_file(): void
    {
        $path = sys_get_temp_dir().'/emporia-builder-'.bin2hex(random_bytes(4)).'.json';
        file_put_contents($path, json_encode((new TokenSet('file-token'))->toArray()));
        $http = (new MockHttp)->json(['customerGid' => 1]);

        try {
            EmporiaConnect::fromOptions(['token_file' => $path], null, $http->client)->customers()->me();
            $this->assertSame('file-token', $http->request(0)->getHeaderLine('authtoken'));
        } finally {
            unlink($path);
        }
    }

    public function test_custom_clock_and_base_uri(): void
    {
        $http = (new MockHttp)->json(['usageList' => []]);

        EmporiaConnect::builder()
            ->withTokens('token')
            ->withClock(new FrozenClock('2024-02-02T10:00:00Z'))
            ->withBaseUri('http://localhost:8000/')
            ->withHttpClient($http->client)
            ->build()
            ->usage()
            ->chart(1, '1');

        $uri = (string) $http->request(0)->getUri();
        $this->assertStringStartsWith('http://localhost:8000/AppAPI', $uri);
        $this->assertStringContainsString('end=2024-02-02T10:00:00.000Z', $uri);
    }

    public function test_maintenance_check_uses_http_client(): void
    {
        $http = (new MockHttp)->json(['msg' => 'down']);

        $this->assertSame('down', EmporiaConnect::fromTokens('token', httpClient: $http->client)->downForMaintenance());
    }
}
