<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Feature;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use T3chW1zard\EmporiaConnect\EmporiaConnect;
use T3chW1zard\EmporiaConnect\Enums\Scale;
use T3chW1zard\EmporiaConnect\Exceptions\AuthenticationException;
use T3chW1zard\EmporiaConnect\Testing\Fixtures;
use T3chW1zard\EmporiaConnect\Tests\Support\FakeCognitoSrpServer;
use T3chW1zard\EmporiaConnect\Tests\Support\Jwt;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

/**
 * Full stack: SRP login against a verifying fake Cognito, token caching, API calls with the id token.
 */
final class EmporiaConnectFeatureTest extends TestCase
{
    private FakeCognitoSrpServer $cognito;

    /** @var list<array{request: RequestInterface}> */
    private array $history = [];

    private int $logins = 0;

    private int $refreshes = 0;

    private string $idToken;

    private string $srpA = '';

    protected function setUp(): void
    {
        $this->cognito = new FakeCognitoSrpServer('ghlOXVLi1', 'user-uuid-1234', 'correct horse');
        $this->idToken = Jwt::make(['exp' => time() + 3600, 'sub' => 'user-uuid-1234']);
    }

    private function http(): GuzzleClient
    {
        $handler = fn (RequestInterface $request) => Create::promiseFor(
            $request->getUri()->getHost() === 'cognito-idp.us-east-2.amazonaws.com'
                ? $this->cognitoResponse(json_decode((string) $request->getBody(), true))
                : $this->apiResponse($request),
        );

        $stack = HandlerStack::create($handler);
        $stack->push(Middleware::history($this->history));

        return new GuzzleClient(['handler' => $stack]);
    }

    /** @param array<string, mixed> $body */
    private function cognitoResponse(array $body): Response
    {
        switch ($body['AuthFlow'] ?? $body['ChallengeName'] ?? null) {
            case 'USER_SRP_AUTH':
                $this->srpA = $body['AuthParameters']['SRP_A'];

                return $this->json(['ChallengeName' => 'PASSWORD_VERIFIER', 'ChallengeParameters' => $this->cognito->challengeParameters()]);

            case 'PASSWORD_VERIFIER':
                if (! $this->cognito->verify($this->srpA, $body['ChallengeResponses'])) {
                    return $this->json(['__type' => 'NotAuthorizedException', 'message' => 'Incorrect username or password.'], 400);
                }

                $this->logins++;

                return $this->json(['AuthenticationResult' => ['IdToken' => $this->idToken, 'AccessToken' => 'access', 'RefreshToken' => 'refresh', 'ExpiresIn' => 3600]]);

            case 'REFRESH_TOKEN_AUTH':
                $this->refreshes++;
                $this->idToken = Jwt::make(['exp' => time() + 7200]);

                return $this->json(['AuthenticationResult' => ['IdToken' => $this->idToken, 'AccessToken' => 'access2', 'ExpiresIn' => 3600]]);
        }

        return $this->json(['__type' => 'InvalidParameterException'], 400);
    }

    private function apiResponse(RequestInterface $request): Response
    {
        if ($request->getHeaderLine('authtoken') !== $this->idToken) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        return match (true) {
            $request->getUri()->getPath() === '/customers/devices' => $this->json(Fixtures::devices()),
            str_starts_with($request->getUri()->getQuery(), 'apiMethod=getChartUsage') => $this->json(Fixtures::chartUsage()),
            default => $this->json(Fixtures::customer()),
        };
    }

    /** @param array<array-key, mixed> $body */
    private function json(array $body, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($body));
    }

    public function test_srp_login_is_accepted_by_a_verifying_server_and_tokens_are_cached(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter);

        $client = EmporiaConnect::client('user@example.com', 'correct horse', $cache, $this->http());

        $this->assertSame(1234, $client->customers()->me()->customerGid);
        $this->assertCount(4, $client->devices()->all());
        $this->assertSame([0.5, 0.25, null, 1.0], $client->usage()->chart(2345, '1,2,3', scale: Scale::HOUR)->usage);
        $this->assertSame(1, $this->logins);

        // A new client (new PHP request) re-uses the cached tokens instead of logging in again.
        $second = EmporiaConnect::client('user@example.com', 'correct horse', $cache, $this->http());
        $second->customers()->me();

        $this->assertSame(1, $this->logins);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $client = EmporiaConnect::client('user@example.com', 'wrong password', httpClient: $this->http());

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Incorrect username or password.');

        $client->customers()->me();
    }

    public function test_rejected_token_is_refreshed_transparently(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter);
        $client = EmporiaConnect::client('user@example.com', 'correct horse', $cache, $this->http());
        $client->customers()->me();

        // The server revokes the current id token.
        $this->idToken = 'revoked';

        $this->assertSame(1234, $client->customers()->me()->customerGid);
        $this->assertSame(1, $this->refreshes);
        $this->assertSame(1, $this->logins);
    }
}
