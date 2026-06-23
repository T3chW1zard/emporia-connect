<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use DateTimeImmutable;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Represents an Emporia customer account.
 */
readonly class CustomerResponse
{
    public function __construct(
        public int $customerGid,
        public string $email,
        public string $firstName,
        public string $lastName,
        public DateTimeImmutable $createdAt,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            customerGid: DataExtractor::int($data, 'customerGid'),
            email: DataExtractor::string($data, 'email'),
            firstName: DataExtractor::string($data, 'firstName'),
            lastName: DataExtractor::string($data, 'lastName'),
            createdAt: new DateTimeImmutable(DataExtractor::string($data, 'createdAt', 'now')),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'customerGid' => $this->customerGid,
            'email'       => $this->email,
            'firstName'   => $this->firstName,
            'lastName'    => $this->lastName,
            'createdAt'   => $this->createdAt->format(DateTimeImmutable::ATOM),
        ];
    }
}
