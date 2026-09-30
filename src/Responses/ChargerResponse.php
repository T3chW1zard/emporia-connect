<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Responses;

use T3chW1zard\EmporiaConnect\Contracts\ResponseContract;
use T3chW1zard\EmporiaConnect\Responses\Concerns\SerializesToJson;
use T3chW1zard\EmporiaConnect\Support\DataExtractor;

/**
 * An Emporia EV charger / EVSE.
 */
final readonly class ChargerResponse implements ResponseContract
{
    use SerializesToJson;

    public function __construct(
        public int $deviceGid,
        public bool $chargerOn,
        public int $chargingRate,
        public int $maxChargingRate,
        public ?int $loadGid = null,
        public ?string $message = null,
        public ?string $status = null,
        public ?string $icon = null,
        public ?string $iconLabel = null,
        public ?string $iconDetailText = null,
        public ?string $faultText = null,
        public bool $offPeakSchedulesEnabled = false,
        public ?string $debugCode = null,
        public ?string $proControlCode = null,
        #[\SensitiveParameter]
        public ?string $breakerPin = null,
    ) {}

    /** @param array<array-key, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            deviceGid: DataExtractor::int($data, 'deviceGid'),
            chargerOn: DataExtractor::bool($data, 'chargerOn'),
            chargingRate: DataExtractor::int($data, 'chargingRate'),
            maxChargingRate: DataExtractor::int($data, 'maxChargingRate'),
            loadGid: DataExtractor::nullableInt($data, 'loadGid'),
            message: DataExtractor::nullableString($data, 'message'),
            status: DataExtractor::nullableString($data, 'status'),
            icon: DataExtractor::nullableString($data, 'icon'),
            iconLabel: DataExtractor::nullableString($data, 'iconLabel'),
            iconDetailText: DataExtractor::nullableString($data, 'iconDetailText'),
            faultText: DataExtractor::nullableString($data, 'faultText'),
            offPeakSchedulesEnabled: DataExtractor::bool($data, 'offPeakSchedulesEnabled'),
            debugCode: DataExtractor::nullableString($data, 'debugCode'),
            proControlCode: DataExtractor::nullableString($data, 'proControlCode'),
            breakerPin: DataExtractor::nullableString($data, 'breakerPIN'),
        );
    }

    public function withChargerOn(bool $on): self
    {
        return $this->copy(chargerOn: $on);
    }

    public function withChargingRate(int $chargingRate): self
    {
        return $this->copy(chargingRate: $chargingRate);
    }

    public function withMaxChargingRate(int $maxChargingRate): self
    {
        return $this->copy(maxChargingRate: $maxChargingRate);
    }

    /**
     * Request body for PUT devices/evcharger.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $payload = [
            'deviceGid' => $this->deviceGid,
            'loadGid' => $this->loadGid,
            'chargerOn' => $this->chargerOn,
            'chargingRate' => $this->chargingRate,
            'maxChargingRate' => $this->maxChargingRate,
        ];

        if ($this->breakerPin !== null && $this->breakerPin !== '') {
            $payload['breakerPIN'] = $this->breakerPin;
        }

        return $payload;
    }

    public function toArray(): array
    {
        return [
            'deviceGid' => $this->deviceGid,
            'loadGid' => $this->loadGid,
            'chargerOn' => $this->chargerOn,
            'message' => $this->message,
            'status' => $this->status,
            'icon' => $this->icon,
            'iconLabel' => $this->iconLabel,
            'iconDetailText' => $this->iconDetailText,
            'faultText' => $this->faultText,
            'chargingRate' => $this->chargingRate,
            'maxChargingRate' => $this->maxChargingRate,
            'offPeakSchedulesEnabled' => $this->offPeakSchedulesEnabled,
            'debugCode' => $this->debugCode,
            'proControlCode' => $this->proControlCode,
        ];
    }

    private function copy(?bool $chargerOn = null, ?int $chargingRate = null, ?int $maxChargingRate = null): self
    {
        return new self(
            deviceGid: $this->deviceGid,
            chargerOn: $chargerOn ?? $this->chargerOn,
            chargingRate: $chargingRate ?? $this->chargingRate,
            maxChargingRate: $maxChargingRate ?? $this->maxChargingRate,
            loadGid: $this->loadGid,
            message: $this->message,
            status: $this->status,
            icon: $this->icon,
            iconLabel: $this->iconLabel,
            iconDetailText: $this->iconDetailText,
            faultText: $this->faultText,
            offPeakSchedulesEnabled: $this->offPeakSchedulesEnabled,
            debugCode: $this->debugCode,
            proControlCode: $this->proControlCode,
            breakerPin: $this->breakerPin,
        );
    }
}
