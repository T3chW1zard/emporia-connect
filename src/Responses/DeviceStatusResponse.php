<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

/**
 * Connection status for all outlets and chargers on an account.
 */
readonly class DeviceStatusResponse
{
    public function __construct(
        /** @var OutletResponse[] */
        public array $outlets,
        /** @var ChargerResponse[] */
        public array $chargers,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        /** @var array<int, array<string, mixed>> $outletItems */
        $outletItems  = is_array($data['outlets'] ?? null) ? $data['outlets'] : [];
        /** @var array<int, array<string, mixed>> $chargerItems */
        $chargerItems = is_array($data['evChargers'] ?? null) ? $data['evChargers'] : [];

        $outlets  = array_map(OutletResponse::from(...), $outletItems);
        $chargers = array_map(ChargerResponse::from(...), $chargerItems);

        return new self(outlets: $outlets, chargers: $chargers);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'outlets'  => array_map(fn(OutletResponse $o): array => $o->toArray(), $this->outlets),
            'chargers' => array_map(fn(ChargerResponse $c): array => $c->toArray(), $this->chargers),
        ];
    }
}
