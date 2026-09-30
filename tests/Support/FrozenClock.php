<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Support;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;

final class FrozenClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct(string $now = '2024-06-01T12:00:00Z')
    {
        $this->now = new DateTimeImmutable($now, new DateTimeZone('UTC'));
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function travel(string $modifier): void
    {
        $this->now = $this->now->modify($modifier);
    }
}
