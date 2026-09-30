<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Testing;

use Closure;
use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;

/**
 * In-memory transport that answers with fixture data and records every request.
 *
 * Routes are "METHOD pattern" keys where "*" matches anything, e.g. "GET vehicles/v2/settings*".
 * Values are response arrays or closures receiving (string $uri, ?array $payload).
 * PUT requests echo the payload merged over the fixture, like the real API does.
 */
final class FakeTransporter implements TransporterContract
{
    /** @var array<string, array<array-key, mixed>|Closure(string, array<string, mixed>|null): array<array-key, mixed>> */
    private array $routes;

    /** @var list<array{method: string, uri: string, payload: array<string, mixed>|null}> */
    private array $sent = [];

    /**
     * @param  array<string, array<array-key, mixed>|Closure(string, array<string, mixed>|null): array<array-key, mixed>>  $responses  overrides for the default routes
     */
    public function __construct(array $responses = [])
    {
        $this->routes = $responses + $this->defaultRoutes();
    }

    public function get(string $uri): array
    {
        return $this->handle('GET', $uri, null);
    }

    public function put(string $uri, array $payload): array
    {
        return $this->handle('PUT', $uri, $payload);
    }

    /**
     * @param  array<array-key, mixed>|Closure(string, array<string, mixed>|null): array<array-key, mixed>  $response
     */
    public function respondWith(string $route, array|Closure $response): self
    {
        $this->routes = [$route => $response] + $this->routes;

        return $this;
    }

    /** @return list<array{method: string, uri: string, payload: array<string, mixed>|null}> */
    public function sent(): array
    {
        return $this->sent;
    }

    /**
     * Requests matching the method and pattern ("*" wildcard).
     *
     * @return list<array{method: string, uri: string, payload: array<string, mixed>|null}>
     */
    public function sentTo(string $method, string $pattern): array
    {
        return array_values(array_filter(
            $this->sent,
            static fn (array $request): bool => $request['method'] === strtoupper($method) && self::matches($pattern, $request['uri']),
        ));
    }

    public function hasSent(string $method, string $pattern): bool
    {
        return $this->sentTo($method, $pattern) !== [];
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<array-key, mixed>
     */
    private function handle(string $method, string $uri, ?array $payload): array
    {
        $this->sent[] = ['method' => $method, 'uri' => $uri, 'payload' => $payload];

        foreach ($this->routes as $route => $response) {
            [$routeMethod, $pattern] = explode(' ', $route, 2) + [1 => ''];

            if (strtoupper($routeMethod) === $method && self::matches($pattern, $uri)) {
                return $response instanceof Closure ? $response($uri, $payload) : $response;
            }
        }

        return [];
    }

    private static function matches(string $pattern, string $uri): bool
    {
        $regex = '#^'.str_replace('\*', '.*', preg_quote($pattern, '#')).'$#';

        return preg_match($regex, $uri) === 1;
    }

    /** @return array<string, array<array-key, mixed>|Closure(string, array<string, mixed>|null): array<array-key, mixed>> */
    private function defaultRoutes(): array
    {
        return [
            'GET customers' => Fixtures::customer(),
            'GET customers/devices' => Fixtures::devices(),
            'GET customers/devices/status' => Fixtures::devicesStatus(),
            'GET customers/vehicles' => Fixtures::vehicles(),
            'GET vehicles/v2/settings*' => Fixtures::vehicleStatus(),
            'GET devices/channels/channeltypes' => Fixtures::channelTypes(),
            'GET devices/*/locationProperties' => static function (string $uri): array {
                $gid = (int) explode('/', $uri)[1];

                return Fixtures::locationProperties($gid);
            },
            'GET AppAPI?apiMethod=getDeviceListUsages*' => Fixtures::deviceListUsages(),
            'GET AppAPI?apiMethod=getChartUsage*' => Fixtures::chartUsage(),
            'PUT devices/outlet' => static fn (string $uri, ?array $payload): array => ($payload ?? []) + Fixtures::outlet(),
            'PUT devices/evcharger' => static fn (string $uri, ?array $payload): array => ($payload ?? []) + Fixtures::charger(),
            'PUT devices/*/channels' => static fn (string $uri, ?array $payload): array => $payload ?? [],
        ];
    }
}
