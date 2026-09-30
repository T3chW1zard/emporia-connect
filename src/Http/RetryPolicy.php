<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Http;

/**
 * Exponential back-off for 5xx responses, same defaults as PyEmVue's Auth.request().
 */
final readonly class RetryPolicy
{
    public function __construct(
        public int $maxAttempts = 5,
        public int $initialDelayMs = 500,
        public int $maxDelayMs = 30000,
    ) {}

    public static function none(): self
    {
        return new self(maxAttempts: 1);
    }

    public function attempts(): int
    {
        return max(1, $this->maxAttempts);
    }

    /**
     * Delay before the given retry (1 = first retry) in milliseconds.
     */
    public function delayMs(int $retry): int
    {
        $delay = max(0, $this->initialDelayMs) * (2 ** max(0, $retry - 1));

        return min($delay, max(0, $this->maxDelayMs));
    }
}
