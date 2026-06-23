<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * Represents an Emporia smart outlet device.
 */
readonly class OutletResponse
{
    public function __construct(
        public int $deviceGid,
        public bool $outletOn,
        public ?int $loadGid,
        /** @var list<mixed> */
        public array $schedules,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        /** @var array<string, mixed> $outlet */
        $outlet = DataExtractor::array($data, 'outlet');

        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            outletOn: DataExtractor::bool($outlet, 'outletOn'),
            loadGid: DataExtractor::nullableInt($outlet, 'loadGid'),
            schedules: array_values(DataExtractor::array($outlet, 'schedules')),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'outletOn' => $this->outletOn,
            'loadGid' => $this->loadGid,
            'schedules' => $this->schedules,
        ];
    }
}
