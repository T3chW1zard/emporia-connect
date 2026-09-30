<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Http;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use T3chW1zard\EmporiaConnect\Exceptions\TransportException;
use T3chW1zard\EmporiaConnect\Http\MaintenanceChecker;
use T3chW1zard\EmporiaConnect\Http\RetryPolicy;
use T3chW1zard\EmporiaConnect\Tests\Support\MockHttp;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class RetryPolicyAndMaintenanceTest extends TestCase
{
    public function test_retry_policy_uses_exponential_backoff_capped_at_max(): void
    {
        $policy = new RetryPolicy(maxAttempts: 5, initialDelayMs: 500, maxDelayMs: 3000);

        $this->assertSame(5, $policy->attempts());
        $this->assertSame(500, $policy->delayMs(1));
        $this->assertSame(1000, $policy->delayMs(2));
        $this->assertSame(2000, $policy->delayMs(3));
        $this->assertSame(3000, $policy->delayMs(4));
        $this->assertSame(1, RetryPolicy::none()->attempts());
        $this->assertSame(1, (new RetryPolicy(maxAttempts: 0))->attempts());
    }

    private function checker(MockHttp $http): MaintenanceChecker
    {
        return new MaintenanceChecker($http->client, new HttpFactory);
    }

    public function test_maintenance_message_is_returned(): void
    {
        $http = (new MockHttp)->json(['msg' => 'down']);

        $this->assertSame('down', $this->checker($http)->check());
        $this->assertSame(MaintenanceChecker::URL, (string) $http->request(0)->getUri());
        $this->assertFalse($http->request(0)->hasHeader('authtoken'));
    }

    public function test_access_denied_and_not_found_mean_no_maintenance(): void
    {
        $http = (new MockHttp)->queue(new Response(403, [], '<Error>AccessDenied</Error>'), new Response(404));

        $this->assertNull($this->checker($http)->check());
        $this->assertNull($this->checker($http)->check());
    }

    public function test_network_error_throws(): void
    {
        $http = (new MockHttp)->queue(new ConnectException('timeout', new Request('GET', 'x')));

        $this->expectException(TransportException::class);

        $this->checker($http)->check();
    }
}
