<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Responses;

use T3chW1zard\EmporiaConnect\Responses\CustomerResponse;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class CustomerResponseTest extends TestCase
{
    private array $fixture = [
        'customerGid' => 12345,
        'email'       => 'user@example.com',
        'firstName'   => 'John',
        'lastName'    => 'Doe',
        'createdAt'   => '2024-01-15T10:30:00+00:00',
    ];

    public function test_creates_from_api_response(): void
    {
        $response = CustomerResponse::from($this->fixture);

        $this->assertSame(12345, $response->customerGid);
        $this->assertSame('user@example.com', $response->email);
        $this->assertSame('John', $response->firstName);
        $this->assertSame('Doe', $response->lastName);
        $this->assertSame('2024-01-15', $response->createdAt->format('Y-m-d'));
    }

    public function test_converts_to_array(): void
    {
        $array = CustomerResponse::from($this->fixture)->toArray();

        $this->assertArrayHasKey('customerGid', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('createdAt', $array);
        $this->assertSame(12345, $array['customerGid']);
    }
}
