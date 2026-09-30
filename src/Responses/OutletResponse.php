<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * An Emporia smart plug.
 */
final readonly class OutletResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $deviceGid,
        public bool $outletOn,
        public ?int $loadGid = null,
        /** @var list<mixed> */
        public array $schedules = [],
        public ?int $parentDeviceGid = null,
        public ?string $parentChannelNum = null,
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            outletOn: DataExtractor::bool($data, 'outletOn'),
            loadGid: DataExtractor::nullableInt($data, 'loadGid'),
            schedules: array_values(DataExtractor::array($data, 'schedules')),
            parentDeviceGid: DataExtractor::nullableInt($data, 'parentDeviceGid'),
            parentChannelNum: DataExtractor::nullableString($data, 'parentChannelNum'),
        );
    }

    public function withOutletOn(bool $on): self
    {
        return new self($this->deviceGid, $on, $this->loadGid, $this->schedules, $this->parentDeviceGid, $this->parentChannelNum);
    }

    /**
     * Request body for PUT devices/outlet.
     *
     * @return array{deviceGid: int, outletOn: bool, loadGid: int|null}
     */
    public function toPayload(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'outletOn' => $this->outletOn,
            'loadGid' => $this->loadGid,
        ];
    }

    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'outletOn' => $this->outletOn,
            'loadGid' => $this->loadGid,
            'schedules' => $this->schedules,
            'parentDeviceGid' => $this->parentDeviceGid,
            'parentChannelNum' => $this->parentChannelNum,
        ];
    }
}
