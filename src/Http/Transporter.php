<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use T3chW1zard\EmporiaConnect\Contracts\TokenProviderContract;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Exceptions\ApiException;
use T3chW1zard\EmporiaConnect\Exceptions\EmporiaException;
use T3chW1zard\EmporiaConnect\Exceptions\TransportException;

/**
 * PSR-18 backed transport to the Emporia API.
 *
 * - sends the Cognito id token in the "authtoken" header (not a Bearer token),
 * - refreshes the tokens and retries once on 401,
 * - retries 5xx responses with exponential back-off,
 * - decodes JSON and throws typed exceptions for failures.
 */
final readonly class Transporter implements TransporterContract
{
    public const BASE_URI = 'https://api.emporiaenergy.com/';

    public function __construct(
        private ClientInterface $http,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private TokenProviderContract $tokens,
        private RetryPolicy $retryPolicy = new RetryPolicy,
        private string $baseUri = self::BASE_URI,
    ) {}

    public function get(string $uri): array
    {
        return $this->send('GET', $uri);
    }

    public function put(string $uri, array $payload): array
    {
        return $this->send('PUT', $uri, $payload);
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<array-key, mixed>
     */
    private function send(string $method, string $uri, ?array $payload = null): array
    {
        $attempts = $this->retryPolicy->attempts();
        $response = null;

        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            if ($attempt > 1) {
                usleep($this->retryPolicy->delayMs($attempt - 1) * 1000);
            }

            $response = $this->dispatch($method, $uri, $payload, $this->tokens->tokens()->idToken);

            if ($response->getStatusCode() === 401) {
                // Token was rejected: refresh it and run the request again.
                $response = $this->dispatch($method, $uri, $payload, $this->tokens->refresh()->idToken);
            }

            if ($response->getStatusCode() < 500) {
                break;
            }
        }

        /** @var ResponseInterface $response */
        $body = (string) $response->getBody();

        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromResponse($method, $uri, $response->getStatusCode(), $body);
        }

        return $this->decode($body, $uri);
    }

    /** @param array<string, mixed>|null $payload */
    private function dispatch(string $method, string $uri, ?array $payload, string $idToken): ResponseInterface
    {
        $request = $this->requestFactory->createRequest($method, rtrim($this->baseUri, '/').'/'.ltrim($uri, '/'))
            ->withHeader('Accept', 'application/json')
            ->withHeader('authtoken', $idToken);

        if ($payload !== null) {
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream(json_encode($payload, JSON_THROW_ON_ERROR)));
        }

        try {
            return $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException(sprintf('Emporia API request %s %s failed: %s', $method, $uri, $e->getMessage()), 0, $e);
        }
    }

    /** @return array<array-key, mixed> */
    private function decode(string $body, string $uri): array
    {
        if (trim($body) === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EmporiaException(sprintf('Failed to decode JSON response from %s: %s', $uri, json_last_error_msg()));
        }

        return is_array($decoded) ? $decoded : [];
    }
}
