<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;

/**
 * Guzzle client backed by a queue of mocked responses that records every request.
 */
final class MockHttp
{
    public readonly MockHandler $handler;

    public readonly Client $client;

    /** @var list<array{request: RequestInterface}> */
    public array $history = [];

    public function __construct()
    {
        $this->handler = new MockHandler;
        $stack = HandlerStack::create($this->handler);
        $stack->push(Middleware::history($this->history));
        $this->client = new Client(['handler' => $stack]);
    }

    public function queue(mixed ...$responses): self
    {
        foreach ($responses as $response) {
            $this->handler->append($response);
        }

        return $this;
    }

    public function json(mixed $body, int $status = 200): self
    {
        return $this->queue(new Response($status, ['Content-Type' => 'application/json'], json_encode($body)));
    }

    public function request(int $index): RequestInterface
    {
        return $this->history[$index]['request'];
    }

    /** @return array<array-key, mixed> */
    public function body(int $index): array
    {
        return json_decode((string) $this->request($index)->getBody(), true);
    }

    public function count(): int
    {
        return count($this->history);
    }

    /** Cognito AuthenticationResult response. */
    public function cognitoTokens(string $idToken, ?string $refreshToken = 'refresh-token', int $expiresIn = 3600): self
    {
        $result = ['IdToken' => $idToken, 'AccessToken' => 'access-token', 'ExpiresIn' => $expiresIn, 'TokenType' => 'Bearer'];

        if ($refreshToken !== null) {
            $result['RefreshToken'] = $refreshToken;
        }

        return $this->json(['AuthenticationResult' => $result]);
    }
}
