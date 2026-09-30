<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Auth;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\Attributes\DataProvider;
use T3chW1zard\EmporiaConnect\Auth\Srp;
use T3chW1zard\EmporiaConnect\Exceptions\AuthenticationException;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class SrpTest extends TestCase
{
    /**
     * Known-answer vector from an independent reference implementation of Cognito SRP,
     * with a fixed private value "a", server value B = 7^123456789 mod N and a salt starting with "f".
     */
    private const LARGE_A_PREFIX = '5cb967af6ec0aa592e56e77f603b2a2fca70ddb04ded61500a730d8cd535a9d2';

    private const SRP_B = '6f877a395a1f15a19574e3edacd11f1b9a47799d998e6b45184d685f383322cb288699ec77b9574755a28696e4b6d494266cc28b108e17ecc018e46111b43c268aac4b61b580a78edcabbc5ef911716d91cb705557fb73f3758522c8878722e9300f4420105b479ccbf2e3aa61fc3d13ce7a3c1e6447c17eed731128bee96e951e856d8c019054750f86e1750148b294cc846a4eaeb97c4223c9bf155b705643647f721c9dbd959648331194142993950afc075448adfa037f6a0b4c0cbb9509dc3c3b583da8bb065ab7da3f07a0432dccd5120c0601106904e10d9661bf86fbb00dcc9881cbbd4d5c471ba794d1e4fbe9abfcc38da89afbc85da74becb2f22bba6742758fed94d2569c6147b99be706301523b9f381df88beea824ebe70f6442063fa49ef19bfc3c9a9dbac5ee57c903606a51f4de15204e20dba7e9bcf6225ea205400f54a48cbd9e1ffe8fa953777ab654a311ba289bdfd53ac5198e1570aa4d42a9ef2027148697120b5b0b0e40367d76e7f4d3c0a1d822014e557d163e4';

    private function smallA(): string
    {
        return str_repeat('ab', 128);
    }

    public function test_large_a_matches_reference_vector(): void
    {
        $srp = new Srp('ghlOXVLi1', $this->smallA());

        $this->assertStringStartsWith(self::LARGE_A_PREFIX, $srp->largeAHex());
        $this->assertSame(768, strlen($srp->largeAHex()));
    }

    public function test_password_claim_signature_matches_reference_vector(): void
    {
        $srp = new Srp('ghlOXVLi1', $this->smallA());

        $response = $srp->processChallenge(
            [
                'USER_ID_FOR_SRP' => 'abc-123-user-id',
                'SALT' => 'f1e2d3c4b5a69788',
                'SRP_B' => self::SRP_B,
                'SECRET_BLOCK' => 'c2VjcmV0LWJsb2NrLWJ5dGVzLTAxMjM0NTY3ODk=',
                'USERNAME' => 'abc-123-user-id',
            ],
            'user@example.com',
            'S3cret!pass',
            new DateTimeImmutable('2024-09-03 05:04:09', new DateTimeZone('UTC')),
        );

        $this->assertSame([
            'TIMESTAMP' => 'Tue Sep 3 05:04:09 UTC 2024',
            'USERNAME' => 'abc-123-user-id',
            'PASSWORD_CLAIM_SECRET_BLOCK' => 'c2VjcmV0LWJsb2NrLWJ5dGVzLTAxMjM0NTY3ODk=',
            'PASSWORD_CLAIM_SIGNATURE' => 'v0hw+e1OesFNAWJLvReGjkiSnMCZNS490of0lVASLqA=',
        ], $response);
    }

    public function test_random_a_differs_between_instances(): void
    {
        $this->assertNotSame((new Srp('pool'))->largeAHex(), (new Srp('pool'))->largeAHex());
    }

    /** @return iterable<string, array{string, string}> */
    public static function timestamps(): iterable
    {
        yield 'single digit day is not padded' => ['2024-09-03 05:04:09', 'Tue Sep 3 05:04:09 UTC 2024'];
        yield 'double digit day' => ['2024-12-25 23:59:01', 'Wed Dec 25 23:59:01 UTC 2024'];
        yield 'converted to UTC' => ['2024-01-01 01:00:00+02:00', 'Sun Dec 31 23:00:00 UTC 2023'];
    }

    #[DataProvider('timestamps')]
    public function test_timestamp_format(string $date, string $expected): void
    {
        $this->assertSame($expected, Srp::timestamp(new DateTimeImmutable($date, new DateTimeZone('UTC'))));
    }

    public function test_missing_challenge_parameter_throws(): void
    {
        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('SRP_B');

        (new Srp('pool', $this->smallA()))->processChallenge(
            ['USER_ID_FOR_SRP' => 'id', 'SALT' => 'ab', 'SECRET_BLOCK' => 'AA=='],
            'user',
            'pass',
            new DateTimeImmutable,
        );
    }

    public function test_server_b_multiple_of_n_is_rejected(): void
    {
        $this->expectException(AuthenticationException::class);

        (new Srp('pool', $this->smallA()))->processChallenge(
            ['USER_ID_FOR_SRP' => 'id', 'SALT' => 'ab', 'SRP_B' => '0', 'SECRET_BLOCK' => 'AA=='],
            'user',
            'pass',
            new DateTimeImmutable,
        );
    }
}
