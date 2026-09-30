<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Resources;

use T3chW1zard\EmporiaConnect\Contracts\TransporterContract;
use T3chW1zard\EmporiaConnect\Responses\CustomerResponse;

/**
 * The logged in customer account.
 */
final readonly class Customers
{
    public function __construct(private TransporterContract $transporter) {}

    /**
     * Details of the logged in customer (PyEmVue: get_customer_details).
     */
    public function me(): CustomerResponse
    {
        return CustomerResponse::from($this->transporter->get('customers'));
    }
}
