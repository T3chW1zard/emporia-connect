<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Support;

final class Jwt
{
    /** Unsigned JWT with the given claims; good enough for expiry parsing. */
    public static function make(array $claims): string
    {
        $encode = static fn (array $data): string => rtrim(strtr(base64_encode(json_encode($data)), '+/', '-_'), '=');

        return $encode(['alg' => 'RS256', 'kid' => 'test']).'.'.$encode($claims).'.signature';
    }
}
