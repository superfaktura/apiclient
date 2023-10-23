<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Invoice;

final class Address
{
    /**
     * @param string|null $name not required, if invoice contains name
     * @param string|null $address not required, if invoice contains address
     * @param string|null $city not required, if invoice contains city
     * @param int|null $country_id not required, if invoice contains country_id
     * @param string|null $state not required, if invoice contains state
     * @param string|null $zip not required, if invoice contains zip
     */
    public function __construct(
        public ?string $name = null,
        public ?string $address = null,
        public ?string $city = null,
        public ?int $country_id = null,
        public ?string $state = null,
        public ?string $zip = null,
    ) {
    }
}
