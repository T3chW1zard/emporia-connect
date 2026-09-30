<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\HttpFactory;
use InvalidArgumentException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\SimpleCache\CacheInterface;
use T3chW1zard\EmporiaConnect\Auth\CognitoClient;
use T3chW1zard\EmporiaConnect\Auth\Stores\FileTokenStore;
use T3chW1zard\EmporiaConnect\Auth\Stores\InMemoryTokenStore;
use T3chW1zard\EmporiaConnect\Auth\Stores\Psr16TokenStore;
use T3chW1zard\EmporiaConnect\Auth\Stores\Psr6TokenStore;
use T3chW1zard\EmporiaConnect\Auth\TokenManager;
use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Contracts\TokenStoreContract;
use T3chW1zard\EmporiaConnect\Enums\AuthFlow;
use T3chW1zard\EmporiaConnect\Http\MaintenanceChecker;
use T3chW1zard\EmporiaConnect\Http\RetryPolicy;
use T3chW1zard\EmporiaConnect\Http\Transporter;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;
use T3chW1zard\EmporiaConnect\Support\SystemClock;

/**
 * Immutable builder for a fully configured {@see Client}.
 *
 * Building does not touch the network: tokens are fetched (from the store, by refreshing or by
 * logging in) on the first API call.
 *
 *   $client = EmporiaConnect::builder()
 *       ->withCredentials('user@example.com', 'secret')
 *       ->withCache($psr16OrPsr6Cache)
 *       ->build();
 */
final class ClientBuilder
{
    public const DEFAULT_CACHE_KEY = 'emporia_connect.tokens';

    public const DEFAULT_CACHE_TTL = 2592000;

    private ?string $username = null;

    private ?string $password = null;

    private ?TokenSet $tokens = null;

    private CacheInterface|CacheItemPoolInterface|null $cache = null;

    private ?string $cacheKey = null;

    private ?int $cacheTtl = self::DEFAULT_CACHE_TTL;

    private ?TokenStoreContract $tokenStore = null;

    private ?ClientInterface $httpClient = null;

    private ?RequestFactoryInterface $requestFactory = null;

    private ?StreamFactoryInterface $streamFactory = null;

    private float $connectTimeout = 6.03;

    private float $readTimeout = 10.03;

    private RetryPolicy $retryPolicy;

    private AuthFlow $authFlow = AuthFlow::SRP;

    private int $tokenRefreshLeeway = 60;

    private ?ClockInterface $clock = null;

    private string $baseUri = Transporter::BASE_URI;

    public function __construct()
    {
        $this->retryPolicy = new RetryPolicy;
    }

    /**
     * Build from a framework configuration array (used by the Laravel and Symfony integrations).
     *
     * Supported keys: username, password, id_token, access_token, refresh_token, token_file,
     * auth_flow ("srp"|"password"), cache_key, cache_ttl, connect_timeout, read_timeout,
     * token_refresh_leeway, retry.max_attempts, retry.initial_delay_ms, retry.max_delay_ms.
     *
     * @param  array<array-key, mixed>  $options
     */
    public static function fromOptions(
        array $options,
        CacheInterface|CacheItemPoolInterface|null $cache = null,
        ?ClientInterface $httpClient = null,
    ): self {
        $builder = new self;

        $username = DataExtractor::nullableString($options, 'username');
        $password = DataExtractor::nullableString($options, 'password');

        if ($username !== null && $username !== '' && $password !== null && $password !== '') {
            $builder = $builder->withCredentials($username, $password);
        }

        $idToken = DataExtractor::nullableString($options, 'id_token');

        if ($idToken !== null && $idToken !== '') {
            $builder = $builder->withTokens(
                $idToken,
                DataExtractor::nullableString($options, 'access_token'),
                DataExtractor::nullableString($options, 'refresh_token'),
            );
        }

        $tokenFile = DataExtractor::nullableString($options, 'token_file');

        if ($tokenFile !== null && $tokenFile !== '') {
            $builder = $builder->withTokenFile($tokenFile);
        } elseif ($cache !== null) {
            $builder = $builder->withCache(
                $cache,
                DataExtractor::nullableString($options, 'cache_key'),
                array_key_exists('cache_ttl', $options) ? DataExtractor::nullableInt($options, 'cache_ttl') : self::DEFAULT_CACHE_TTL,
            );
        }

        if ($httpClient instanceof ClientInterface) {
            $builder = $builder->withHttpClient($httpClient);
        }

        $flow = DataExtractor::nullableString($options, 'auth_flow');

        if ($flow !== null && $flow !== '') {
            $builder = $builder->withAuthFlow(match (strtolower($flow)) {
                'srp', strtolower(AuthFlow::SRP->value) => AuthFlow::SRP,
                'password', strtolower(AuthFlow::PASSWORD->value) => AuthFlow::PASSWORD,
                default => throw new InvalidArgumentException("Unknown auth_flow '{$flow}', expected 'srp' or 'password'."),
            });
        }

        $retry = DataExtractor::array($options, 'retry');
        $defaults = new RetryPolicy;

        return $builder
            ->withTimeouts(
                DataExtractor::float($options, 'connect_timeout', 6.03),
                DataExtractor::float($options, 'read_timeout', 10.03),
            )
            ->withRetryPolicy(new RetryPolicy(
                maxAttempts: DataExtractor::int($retry, 'max_attempts', $defaults->maxAttempts),
                initialDelayMs: DataExtractor::int($retry, 'initial_delay_ms', $defaults->initialDelayMs),
                maxDelayMs: DataExtractor::int($retry, 'max_delay_ms', $defaults->maxDelayMs),
            ))
            ->withTokenRefreshLeeway(DataExtractor::int($options, 'token_refresh_leeway', 60));
    }

    /**
     * Log in with the Emporia account email and password when no (valid) tokens are available.
     */
    public function withCredentials(string $username, #[\SensitiveParameter] string $password): self
    {
        $clone = clone $this;
        $clone->username = strtolower(trim($username));
        $clone->password = $password;

        return $clone;
    }

    /**
     * Start from existing tokens instead of logging in (PyEmVue: login(id_token=..., access_token=..., refresh_token=...)).
     */
    public function withTokens(string $idToken, ?string $accessToken = null, ?string $refreshToken = null): self
    {
        return $this->withTokenSet(TokenSet::fromTokens($idToken, $accessToken, $refreshToken));
    }

    public function withTokenSet(TokenSet $tokens): self
    {
        $clone = clone $this;
        $clone->tokens = $tokens;

        return $clone;
    }

    /**
     * Cache tokens in a PSR-16 or PSR-6 cache so the client does not log in on every request.
     *
     * @param  string|null  $key  defaults to a key derived from the username
     * @param  int|null  $ttl  seconds; the entry contains the refresh token (valid for 30 days by default)
     */
    public function withCache(CacheInterface|CacheItemPoolInterface $cache, ?string $key = null, ?int $ttl = self::DEFAULT_CACHE_TTL): self
    {
        $clone = clone $this;
        $clone->cache = $cache;
        $clone->cacheKey = $key;
        $clone->cacheTtl = $ttl;
        $clone->tokenStore = null;

        return $clone;
    }

    /**
     * Store tokens in a JSON file (PyEmVue: token_storage_file).
     */
    public function withTokenFile(string $path): self
    {
        return $this->withTokenStore(new FileTokenStore($path));
    }

    /**
     * Use a custom token store, e.g. a database backed one.
     */
    public function withTokenStore(TokenStoreContract $store): self
    {
        $clone = clone $this;
        $clone->tokenStore = $store;
        $clone->cache = null;

        return $clone;
    }

    /**
     * Use your own PSR-18 client (and optionally PSR-17 factories). Defaults to Guzzle.
     * Timeouts configured with {@see self::withTimeouts()} only apply to the default client.
     */
    public function withHttpClient(
        ClientInterface $client,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ): self {
        $clone = clone $this;
        $clone->httpClient = $client;
        $clone->requestFactory = $requestFactory;
        $clone->streamFactory = $streamFactory;

        return $clone;
    }

    public function withTimeouts(float $connectTimeout, float $readTimeout): self
    {
        $clone = clone $this;
        $clone->connectTimeout = $connectTimeout;
        $clone->readTimeout = $readTimeout;

        return $clone;
    }

    public function withRetryPolicy(RetryPolicy $retryPolicy): self
    {
        $clone = clone $this;
        $clone->retryPolicy = $retryPolicy;

        return $clone;
    }

    public function withAuthFlow(AuthFlow $authFlow): self
    {
        $clone = clone $this;
        $clone->authFlow = $authFlow;

        return $clone;
    }

    /**
     * Refresh the id token this many seconds before it expires.
     */
    public function withTokenRefreshLeeway(int $seconds): self
    {
        $clone = clone $this;
        $clone->tokenRefreshLeeway = max(0, $seconds);

        return $clone;
    }

    public function withClock(ClockInterface $clock): self
    {
        $clone = clone $this;
        $clone->clock = $clock;

        return $clone;
    }

    public function withBaseUri(string $baseUri): self
    {
        $clone = clone $this;
        $clone->baseUri = $baseUri;

        return $clone;
    }

    public function build(): ClientContract
    {
        $http = $this->httpClient ?? new GuzzleClient([
            'connect_timeout' => $this->connectTimeout,
            'read_timeout' => $this->readTimeout,
            'timeout' => $this->connectTimeout + $this->readTimeout,
            'headers' => ['User-Agent' => 'emporia-connect-php'],
        ]);
        $factory = new HttpFactory;
        $requestFactory = $this->requestFactory ?? $factory;
        $streamFactory = $this->streamFactory ?? $factory;
        $clock = $this->clock ?? new SystemClock;

        $tokens = new TokenManager(
            cognito: new CognitoClient($http, $requestFactory, $streamFactory, $this->authFlow, clock: $clock),
            store: $this->resolveTokenStore(),
            username: $this->username,
            password: $this->password,
            initialTokens: $this->tokens,
            leewaySeconds: $this->tokenRefreshLeeway,
            clock: $clock,
        );

        return new Client(
            transporter: new Transporter($http, $requestFactory, $streamFactory, $tokens, $this->retryPolicy, $this->baseUri),
            maintenance: new MaintenanceChecker($http, $requestFactory),
            clock: $clock,
        );
    }

    private function resolveTokenStore(): TokenStoreContract
    {
        if ($this->tokenStore instanceof TokenStoreContract) {
            return $this->tokenStore;
        }

        $key = $this->cacheKey ?? ($this->username !== null
            ? self::DEFAULT_CACHE_KEY.'.'.hash('sha256', $this->username.'|'.CognitoClient::CLIENT_ID)
            : self::DEFAULT_CACHE_KEY);

        return match (true) {
            $this->cache instanceof CacheInterface => new Psr16TokenStore($this->cache, $key, $this->cacheTtl),
            $this->cache instanceof CacheItemPoolInterface => new Psr6TokenStore($this->cache, $key, $this->cacheTtl),
            default => new InMemoryTokenStore,
        };
    }
}
