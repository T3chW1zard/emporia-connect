<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Auth;

use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use T3chW1zard\EmporiaConnect\Enums\AuthFlow;
use T3chW1zard\EmporiaConnect\Exceptions\AuthenticationException;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;
use T3chW1zard\EmporiaConnect\Support\SystemClock;

/**
 * Minimal AWS Cognito Identity Provider client for the Emporia user pool.
 *
 * Supports logging in (SRP or plain password) and refreshing tokens with a refresh token.
 */
final readonly class CognitoClient
{
    public const REGION = 'us-east-2';

    public const USER_POOL_ID = 'us-east-2_ghlOXVLi1';

    public const CLIENT_ID = '4qte47jbstod8apnfic0bunmrq';

    private ClockInterface $clock;

    public function __construct(
        private ClientInterface $http,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
        private AuthFlow $authFlow = AuthFlow::SRP,
        private string $userPoolId = self::USER_POOL_ID,
        private string $clientId = self::CLIENT_ID,
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new SystemClock;
    }

    /**
     * Log in with username and password.
     *
     * @throws AuthenticationException
     */
    public function authenticate(string $username, string $password): TokenSet
    {
        return match ($this->authFlow) {
            AuthFlow::SRP => $this->authenticateWithSrp($username, $password),
            AuthFlow::PASSWORD => $this->tokensFrom($this->call('InitiateAuth', [
                'AuthFlow' => AuthFlow::PASSWORD->value,
                'ClientId' => $this->clientId,
                'AuthParameters' => ['USERNAME' => $username, 'PASSWORD' => $password],
            ])),
        };
    }

    /**
     * Get new id/access tokens using a refresh token (REFRESH_TOKEN_AUTH).
     *
     * @throws AuthenticationException
     */
    public function refresh(string $refreshToken): TokenSet
    {
        $response = $this->call('InitiateAuth', [
            'AuthFlow' => 'REFRESH_TOKEN_AUTH',
            'ClientId' => $this->clientId,
            'AuthParameters' => ['REFRESH_TOKEN' => $refreshToken],
        ]);

        return $this->tokensFrom($response, $refreshToken);
    }

    public function endpoint(): string
    {
        $region = explode('_', $this->userPoolId, 2)[0];

        return "https://cognito-idp.{$region}.amazonaws.com/";
    }

    private function authenticateWithSrp(string $username, string $password): TokenSet
    {
        $poolName = explode('_', $this->userPoolId, 2)[1] ?? $this->userPoolId;
        $srp = new Srp($poolName);

        $challenge = $this->call('InitiateAuth', [
            'AuthFlow' => AuthFlow::SRP->value,
            'ClientId' => $this->clientId,
            'AuthParameters' => ['USERNAME' => $username, 'SRP_A' => $srp->largeAHex()],
        ]);

        $challengeName = DataExtractor::string($challenge, 'ChallengeName');

        if ($challengeName !== 'PASSWORD_VERIFIER') {
            throw new AuthenticationException("Unexpected Cognito challenge '{$challengeName}', expected PASSWORD_VERIFIER.");
        }

        $response = $this->call('RespondToAuthChallenge', [
            'ChallengeName' => 'PASSWORD_VERIFIER',
            'ClientId' => $this->clientId,
            'ChallengeResponses' => $srp->processChallenge(
                DataExtractor::array($challenge, 'ChallengeParameters'),
                $username,
                $password,
                $this->clock->now(),
            ),
        ]);

        return $this->tokensFrom($response);
    }

    /** @param array<array-key, mixed> $response */
    private function tokensFrom(array $response, ?string $previousRefreshToken = null): TokenSet
    {
        $challengeName = DataExtractor::nullableString($response, 'ChallengeName');

        if ($challengeName !== null) {
            throw new AuthenticationException("Cognito requires the '{$challengeName}' challenge, which is not supported. Complete it in the Emporia app first.");
        }

        $tokens = TokenSet::fromCognito(DataExtractor::array($response, 'AuthenticationResult'), $this->clock->now(), $previousRefreshToken);

        if (! $tokens instanceof TokenSet) {
            throw new AuthenticationException('Cognito response did not contain an IdToken.');
        }

        return $tokens;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<array-key, mixed>
     */
    private function call(string $target, array $payload): array
    {
        $request = $this->requestFactory->createRequest('POST', $this->endpoint())
            ->withHeader('X-Amz-Target', 'AWSCognitoIdentityProviderService.'.$target)
            ->withHeader('Content-Type', 'application/x-amz-json-1.1')
            ->withBody($this->streamFactory->createStream(json_encode($payload, JSON_THROW_ON_ERROR)));

        try {
            $response = $this->http->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new AuthenticationException('HTTP request to Cognito failed: '.$e->getMessage(), 0, $e);
        }

        $decoded = json_decode((string) $response->getBody(), true);
        $body = is_array($decoded) ? $decoded : [];

        if ($response->getStatusCode() !== 200) {
            $type = DataExtractor::string($body, '__type', 'UnknownError');
            $type = str_contains($type, '#') ? substr($type, (int) strrpos($type, '#') + 1) : $type;
            $message = DataExtractor::nullableString($body, 'message') ?? DataExtractor::string($body, 'Message', 'no message');

            throw new AuthenticationException(sprintf(
                'Cognito %s failed with status %d (%s): %s',
                $target,
                $response->getStatusCode(),
                $type,
                $message,
            ), $response->getStatusCode());
        }

        return $body;
    }
}
