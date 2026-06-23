<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Exceptions\EmporiaException;

/**
 * Guzzle-backed HTTP transport. Adds auth header and decodes JSON responses.
 */
final readonly class Transporter implements TransporterContract
{
    private const string BASE_URL = 'https://api.emporiaenergy.com/';

    public function __construct(
        private Client $client,
        private string $token,
    ) {}

    /** @return array<string, mixed> */
    public function get(string $uri): array
    {
        try {
            $response = $this->client->get(self::BASE_URL.$uri, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authtoken' => 'Bearer '.$this->token,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new EmporiaException('Emporia API request failed: '.$e->getMessage(), $e->getCode(), previous: $e);
        }

        return $this->decodeJson((string) $response->getBody(), $uri);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function put(string $uri, array $payload): array
    {
        try {
            $response = $this->client->put(self::BASE_URL.$uri, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authtoken' => 'Bearer '.$this->token,
                ],
                'json' => $payload,
            ]);
        } catch (GuzzleException $e) {
            throw new EmporiaException('Emporia API request failed: '.$e->getMessage(), $e->getCode(), previous: $e);
        }

        return $this->decodeJson((string) $response->getBody(), $uri);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws EmporiaException
     */
    private function decodeJson(string $body, string $uri): array
    {
        $decoded = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new EmporiaException(sprintf(
                'Failed to decode JSON response from %s: %s',
                $uri,
                json_last_error_msg(),
            ));
        }

        return is_array($decoded) ? $decoded : [];
    }
}
