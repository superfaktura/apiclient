<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Invoice;

use SuperFaktura\ApiClient\Filter\Sort;
use SuperFaktura\ApiClient\Filter\TimePeriod;
use SuperFaktura\ApiClient\Contract\PaymentType;
use SuperFaktura\ApiClient\Filter\SortDirection;
use SuperFaktura\ApiClient\Contract\Invoice\DeliveryType;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceStatus;

final class InvoicesQuery
{
    private const ITEMS_PER_PAGE_MAX = 200;

    public const VALUES_SEPARATOR = '|';

    /**
     * @param PaymentType[] $payment_types
     * @param DeliveryType[] $delivery_types
     * @param int[] $ignored_invoices
     */
    public function __construct(
        public ?string $full_text = null,
        public ?int $client_id = null,
        public ?string $formatted_number = null,
        public ?string $order_number = null,
        public ?string $variable_symbol = null,
        public ?InvoiceStatus $status = null,
        public array $payment_types = [],
        public array $delivery_types = [],
        public ?float $amount_from = null,
        public ?float $amount_to = null,
        public ?int $tag = null,
        public ?TimePeriod $delivery = null,
        public ?TimePeriod $paid = null,
        public ?TimePeriod $created = null,
        public ?TimePeriod $modified = null,
        public Sort $sort = new Sort('id', SortDirection::DESC),
        public int $page = 1,
        public int $items_per_page = self::ITEMS_PER_PAGE_MAX,
        public array $ignored_invoices = [],
    ) {
        if ($this->page < 1) {
            throw new \InvalidArgumentException('Page argument must be greater than or equal to 1');
        }

        if ($this->items_per_page < 1 || $this->items_per_page > self::ITEMS_PER_PAGE_MAX) {
            throw new \InvalidArgumentException(sprintf(
                'Items per page argument must be greater than or equal to 1 and less than %d',
                self::ITEMS_PER_PAGE_MAX,
            ));
        }
    }
}
