<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Auth;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use T3chW1zard\EmporiaConnect\Auth\CognitoClient;
use T3chW1zard\EmporiaConnect\Enums\AuthFlow;
use T3chW1zard\EmporiaConnect\Exceptions\AuthenticationException;
use T3chW1zard\EmporiaConnect\Tests\Support\FrozenClock;
use T3chW1zard\EmporiaConnect\Tests\Support\MockHttp;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class CognitoClientTest extends TestCase
{
    private MockHttp $http;

    protected function setUp(): void
    {
        $this->http = new MockHttp;
    }

    private function cognito(AuthFlow $flow = AuthFlow::SRP): CognitoClient
    {
        $factory = new HttpFactory;

        return new CognitoClient($this->http->client, $factory, $factory, $flow, clock: new FrozenClock('2024-09-03T05:04:09Z'));
    }

    public function test_srp_login_answers_the_password_verifier_challenge(): void
    {
        $this->http
            ->json([
                'ChallengeName' => 'PASSWORD_VERIFIER',
                'ChallengeParameters' => [
                    'USER_ID_FOR_SRP' => 'user-uuid',
                    'USERNAME' => 'user-uuid',
                    'SALT' => 'f1e2d3c4b5a69788',
                    'SRP_B' => str_repeat('ab', 384),
                    'SECRET_BLOCK' => base64_encode('secret-block'),
                ],
            ])
            ->cognitoTokens('id-token');

        $tokens = $this->cognito()->authenticate('user@example.com', 'secret');

        $this->assertSame('id-token', $tokens->idToken);
        $this->assertSame('refresh-token', $tokens->refreshToken);
        $this->assertSame(2, $this->http->count());

        $initiate = $this->http->request(0);
        $this->assertSame('POST', $initiate->getMethod());
        $this->assertSame('https://cognito-idp.us-east-2.amazonaws.com/', (string) $initiate->getUri());
        $this->assertSame('AWSCognitoIdentityProviderService.InitiateAuth', $initiate->getHeaderLine('X-Amz-Target'));
        $this->assertSame('application/x-amz-json-1.1', $initiate->getHeaderLine('Content-Type'));

        $initiateBody = $this->http->body(0);
        $this->assertSame('USER_SRP_AUTH', $initiateBody['AuthFlow']);
        $this->assertSame(CognitoClient::CLIENT_ID, $initiateBody['ClientId']);
        $this->assertSame('user@example.com', $initiateBody['AuthParameters']['USERNAME']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]+$/', $initiateBody['AuthParameters']['SRP_A']);
        $this->assertArrayNotHasKey('PASSWORD', $initiateBody['AuthParameters']);

        $this->assertSame('AWSCognitoIdentityProviderService.RespondToAuthChallenge', $this->http->request(1)->getHeaderLine('X-Amz-Target'));
        $respondBody = $this->http->body(1);
        $this->assertSame('PASSWORD_VERIFIER', $respondBody['ChallengeName']);
        $this->assertSame('Tue Sep 3 05:04:09 UTC 2024', $respondBody['ChallengeResponses']['TIMESTAMP']);
        $this->assertSame('user-uuid', $respondBody['ChallengeResponses']['USERNAME']);
        $this->assertSame(base64_encode('secret-block'), $respondBody['ChallengeResponses']['PASSWORD_CLAIM_SECRET_BLOCK']);
        $this->assertNotEmpty($respondBody['ChallengeResponses']['PASSWORD_CLAIM_SIGNATURE']);
    }

    public function test_srp_login_rejects_unexpected_challenge(): void
    {
        $this->http->json(['ChallengeName' => 'CUSTOM_CHALLENGE', 'ChallengeParameters' => []]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('CUSTOM_CHALLENGE');

        $this->cognito()->authenticate('user@example.com', 'secret');
    }

    public function test_password_flow_sends_password(): void
    {
        $this->http->cognitoTokens('id-token');

        $tokens = $this->cognito(AuthFlow::PASSWORD)->authenticate('user@example.com', 'secret');

        $this->assertSame('id-token', $tokens->idToken);
        $body = $this->http->body(0);
        $this->assertSame('USER_PASSWORD_AUTH', $body['AuthFlow']);
        $this->assertSame(['USERNAME' => 'user@example.com', 'PASSWORD' => 'secret'], $body['AuthParameters']);
    }

    public function test_mfa_challenge_is_reported(): void
    {
        $this->http->json(['ChallengeName' => 'SOFTWARE_TOKEN_MFA', 'Session' => 'x']);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('SOFTWARE_TOKEN_MFA');

        $this->cognito(AuthFlow::PASSWORD)->authenticate('user@example.com', 'secret');
    }

    public function test_refresh_keeps_the_refresh_token(): void
    {
        $this->http->cognitoTokens('new-id-token', refreshToken: null);

        $tokens = $this->cognito()->refresh('my-refresh-token');

        $this->assertSame('new-id-token', $tokens->idToken);
        $this->assertSame('my-refresh-token', $tokens->refreshToken);
        $body = $this->http->body(0);
        $this->assertSame('REFRESH_TOKEN_AUTH', $body['AuthFlow']);
        $this->assertSame(['REFRESH_TOKEN' => 'my-refresh-token'], $body['AuthParameters']);
    }

    public function test_cognito_error_is_translated(): void
    {
        $this->http->json(['__type' => 'com.amazon#NotAuthorizedException', 'message' => 'Incorrect username or password.'], 400);

        try {
            $this->cognito(AuthFlow::PASSWORD)->authenticate('user@example.com', 'wrong');
            $this->fail('Expected exception');
        } catch (AuthenticationException $e) {
            $this->assertSame(400, $e->getCode());
            $this->assertStringContainsString('(NotAuthorizedException)', $e->getMessage());
            $this->assertStringContainsString('Incorrect username or password.', $e->getMessage());
        }
    }

    public function test_missing_id_token_throws(): void
    {
        $this->http->json(['AuthenticationResult' => ['AccessToken' => 'x']]);

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('IdToken');

        $this->cognito(AuthFlow::PASSWORD)->authenticate('user@example.com', 'secret');
    }

    public function test_network_error_throws_authentication_exception(): void
    {
        $this->http->queue(new ConnectException('Connection refused', new Request('POST', 'x')));

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('Connection refused');

        $this->cognito(AuthFlow::PASSWORD)->authenticate('user@example.com', 'secret');
    }

    public function test_endpoint_is_derived_from_the_pool_region(): void
    {
        $factory = new HttpFactory;
        $cognito = new CognitoClient($this->http->client, $factory, $factory, userPoolId: 'eu-west-1_abc');

        $this->assertSame('https://cognito-idp.eu-west-1.amazonaws.com/', $cognito->endpoint());
    }
}
