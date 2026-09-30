<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Http;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use T3chW1zard\EmporiaConnect\Exceptions\TransportException;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Checks whether the Emporia API is down for maintenance (PyEmVue's down_for_maintenance).
 *
 * During normal operation the S3 file answers "access denied"/404; during maintenance it
 * contains e.g. {"msg": "down"}.
 */
final readonly class MaintenanceChecker
{
    public const URL = 'https://s3.amazonaws.com/com.emporiaenergy.manual.ota/maintenance/maintenance.json';

    public function __construct(
        private ClientInterface $http,
        private RequestFactoryInterface $requestFactory,
        private string $url = self::URL,
    ) {}

    /**
     * The maintenance message, or null when the API is up.
     *
     * @throws TransportException
     */
    public function check(): ?string
    {
        try {
            $response = $this->http->sendRequest($this->requestFactory->createRequest('GET', $this->url));
        } catch (ClientExceptionInterface $e) {
            throw new TransportException('Maintenance check failed: '.$e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $decoded = json_decode((string) $response->getBody(), true);

        return is_array($decoded) ? DataExtractor::nullableString($decoded, 'msg') : null;
    }
}
