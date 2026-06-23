<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect;

use GuzzleHttp\Client as GuzzleClient;
use Psr\SimpleCache\CacheInterface;
use T3chW1zard\EmporiaConnect\Auth\CognitoAuth;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;

/**
 * Static factory for creating an authenticated Emporia Connect client.
 *
 * Usage:
 *   $client = EmporiaConnect::client(username: '...', password: '...');
 */
final class EmporiaConnect
{
    private const string COGNITO_URL = 'https://cognito-idp.us-east-2.amazonaws.com/';
    private const string COGNITO_CLIENT_ID = '4qte47jbstod8apnfic0bunmrq';

    public static function client(
        string $username,
        string $password,
        ?CacheInterface $cache = null,
    ): ClientContract {
        $guzzle = new GuzzleClient(['timeout' => 10]);

        $auth = new CognitoAuth(
            client: $guzzle,
            cognitoUrl: self::COGNITO_URL,
            clientId: self::COGNITO_CLIENT_ID,
            cache: $cache,
        );

        $token = $auth->authenticate($username, $password);

        return new Client(new Transporter($guzzle, $token));
    }
}
