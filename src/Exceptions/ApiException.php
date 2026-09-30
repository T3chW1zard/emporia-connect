<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Exceptions;

use Throwable;

/**
 * Thrown when the Emporia API answers with a non-successful HTTP status code.
 */
class ApiException extends EmporiaException
{
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly string $responseBody = '',
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public static function fromResponse(string $method, string $uri, int $statusCode, string $body): self
    {
        return new self(
            sprintf('Emporia API request %s %s failed with status %d: %s', $method, $uri, $statusCode, substr($body, 0, 500)),
            $statusCode,
            $body,
        );
    }
}
