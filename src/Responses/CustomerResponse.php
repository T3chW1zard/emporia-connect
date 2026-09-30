<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use DateTimeImmutable;
use DateTimeInterface;
use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * The Emporia customer account (PyEmVue: Customer).
 */
final readonly class CustomerResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $customerGid,
        public string $email,
        public string $firstName,
        public string $lastName,
        public ?DateTimeImmutable $createdAt,
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            customerGid: DataExtractor::int($data, 'customerGid'),
            email: DataExtractor::string($data, 'email'),
            firstName: DataExtractor::string($data, 'firstName'),
            lastName: DataExtractor::string($data, 'lastName'),
            createdAt: DataExtractor::nullableDate($data, 'createdAt'),
        );
    }

    public function fullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }

    public function toArray(): array
    {
        return [
            'customerGid' => $this->customerGid,
            'email' => $this->email,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'createdAt' => $this->createdAt?->format(DateTimeInterface::ATOM),
        ];
    }
}
