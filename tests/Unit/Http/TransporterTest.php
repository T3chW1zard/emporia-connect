<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Http;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use T3chW1zard\EmporiaConnect\Exceptions\ApiException;
use T3chW1zard\EmporiaConnect\Exceptions\EmporiaException;
use T3chW1zard\EmporiaConnect\Exceptions\TransportException;
use T3chW1zard\EmporiaConnect\Http\RetryPolicy;
use T3chW1zard\EmporiaConnect\Http\Transporter;
use T3chW1zard\EmporiaConnect\Tests\Support\MockHttp;
use T3chW1zard\EmporiaConnect\Tests\Support\StaticTokenProvider;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class TransporterTest extends TestCase
{
    private MockHttp $http;

    private StaticTokenProvider $tokens;

    protected function setUp(): void
    {
        $this->http = new MockHttp;
        $this->tokens = new StaticTokenProvider;
    }

    private function transporter(?RetryPolicy $retry = null): Transporter
    {
        $factory = new HttpFactory;

        return new Transporter($this->http->client, $factory, $factory, $this->tokens, $retry ?? new RetryPolicy(3, 0, 0));
    }

    public function test_get_sends_raw_id_token_in_authtoken_header(): void
    {
        $this->http->json(['customerGid' => 1]);

        $data = $this->transporter()->get('customers');

        $this->assertSame(['customerGid' => 1], $data);
        $request = $this->http->request(0);
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('https://api.emporiaenergy.com/customers', (string) $request->getUri());
        $this->assertSame('id-token-1', $request->getHeaderLine('authtoken'));
        $this->assertFalse($request->hasHeader('Authorization'));
        $this->assertSame('application/json', $request->getHeaderLine('Accept'));
    }

    public function test_query_string_is_sent_verbatim(): void
    {
        $this->http->json([]);

        $this->transporter()->get('AppAPI?apiMethod=getDeviceListUsages&deviceGids=1+2&scale=1S');

        $this->assertSame('apiMethod=getDeviceListUsages&deviceGids=1+2&scale=1S', $this->http->request(0)->getUri()->getQuery());
    }

    public function test_put_sends_json_body(): void
    {
        $this->http->json(['deviceGid' => 1, 'outletOn' => true]);

        $this->transporter()->put('devices/outlet', ['deviceGid' => 1, 'outletOn' => true, 'loadGid' => null]);

        $request = $this->http->request(0);
        $this->assertSame('PUT', $request->getMethod());
        $this->assertSame('application/json', $request->getHeaderLine('Content-Type'));
        $this->assertSame(['deviceGid' => 1, 'outletOn' => true, 'loadGid' => null], $this->http->body(0));
    }

    public function test_list_responses_are_returned(): void
    {
        $this->http->json([['vehicleGid' => 1], ['vehicleGid' => 2]]);

        $this->assertCount(2, $this->transporter()->get('customers/vehicles'));
    }

    public function test_401_refreshes_the_token_and_retries_once(): void
    {
        $this->http->json(['message' => 'Unauthorized'], 401)->json(['ok' => true]);

        $this->assertSame(['ok' => true], $this->transporter()->get('customers'));
        $this->assertSame(1, $this->tokens->refreshes);
        $this->assertSame('id-token-2', $this->http->request(1)->getHeaderLine('authtoken'));
    }

    public function test_persistent_401_throws_api_exception(): void
    {
        $this->http->json([], 401)->json([], 401);

        try {
            $this->transporter()->get('customers');
            $this->fail('Expected exception');
        } catch (ApiException $e) {
            $this->assertSame(401, $e->statusCode);
            $this->assertSame(2, $this->http->count());
        }
    }

    public function test_server_errors_are_retried(): void
    {
        $this->http->json([], 503)->json([], 500)->json(['ok' => true]);

        $this->assertSame(['ok' => true], $this->transporter()->get('customers'));
        $this->assertSame(3, $this->http->count());
    }

    public function test_server_errors_give_up_after_max_attempts(): void
    {
        $this->http->json(['error' => 'boom'], 500)->json(['error' => 'boom'], 500)->json(['error' => 'boom'], 500);

        try {
            $this->transporter()->get('customers');
            $this->fail('Expected exception');
        } catch (ApiException $e) {
            $this->assertSame(500, $e->statusCode);
            $this->assertStringContainsString('boom', $e->responseBody);
            $this->assertSame(3, $this->http->count());
        }
    }

    public function test_client_errors_are_not_retried(): void
    {
        $this->http->json(['error' => 'bad'], 400);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('GET customers failed with status 400');

        $this->transporter()->get('customers');
    }

    public function test_network_errors_throw_transport_exception(): void
    {
        $this->http->queue(new ConnectException('Connection refused', new Request('GET', 'x')));

        $this->expectException(TransportException::class);

        $this->transporter()->get('customers');
    }

    public function test_empty_body_returns_empty_array(): void
    {
        $this->http->queue(new Response(200, [], ''));

        $this->assertSame([], $this->transporter()->get('vehicles/v2/settings?vehicleGid=1'));
    }

    public function test_invalid_json_throws(): void
    {
        $this->http->queue(new Response(200, [], '{invalid'));

        $this->expectException(EmporiaException::class);
        $this->expectExceptionMessage('Failed to decode JSON');

        $this->transporter()->get('customers');
    }

    public function test_custom_base_uri(): void
    {
        $factory = new HttpFactory;
        $this->http->json([]);

        (new Transporter($this->http->client, $factory, $factory, $this->tokens, RetryPolicy::none(), 'http://localhost:8000'))->get('/customers');

        $this->assertSame('http://localhost:8000/customers', (string) $this->http->request(0)->getUri());
    }
}
