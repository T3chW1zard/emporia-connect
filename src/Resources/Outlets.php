<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Responses\OutletResponse;

/**
 * Outlet resource — list and toggle.
 */
final readonly class Outlets
{
    public function __construct(private TransporterContract $transporter) {}

    /** @return OutletResponse[] */
    public function all(): array
    {
        $data = $this->transporter->get('customers/devices/status');
        /** @var array<int, array<string, mixed>> $items */
        $items = is_array($data['outlets'] ?? null) ? $data['outlets'] : [];

        return array_map(OutletResponse::from(...), $items);
    }

    public function update(int $deviceGid, bool $on): OutletResponse
    {
        $data = $this->transporter->put('devices/outlet', [
            'deviceGid' => $deviceGid,
            'outlet'    => ['outletOn' => $on],
        ]);

        return OutletResponse::from($data);
    }
}
