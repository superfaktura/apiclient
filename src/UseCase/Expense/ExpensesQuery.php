<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Expense;

use SuperFaktura\ApiClient\Filter\Sort;
use SuperFaktura\ApiClient\Filter\TimePeriod;
use SuperFaktura\ApiClient\Contract\PaymentType;
use SuperFaktura\ApiClient\Filter\SortDirection;
use SuperFaktura\ApiClient\Contract\Expense\ExpenseType;
use SuperFaktura\ApiClient\Contract\Expense\ExpenseStatus;

final class ExpensesQuery
{
    private const ITEMS_PER_PAGE_DEFAULT = 100;

    public const VALUES_SEPARATOR = '|';

    /**
     * @param ExpenseStatus[] $statuses
     */
    public function __construct(
        public ?string $full_text = null,
        public ?int $client_id = null,
        public ?int $category_id = null,
        public ?ExpenseType $type = null,
        public ?PaymentType $payment_type = null,
        public array $statuses = [],
        public ?float $amount_from = null,
        public ?float $amount_to = null,
        public ?TimePeriod $delivery = null,
        public ?TimePeriod $created = null,
        public ?TimePeriod $modified = null,
        public ?TimePeriod $due = null,
        public Sort $sort = new Sort('id', SortDirection::DESC),
        public int $page = 1,
        public int $items_per_page = self::ITEMS_PER_PAGE_DEFAULT,
    ) {
        if ($this->page < 1) {
            throw new \InvalidArgumentException('Page argument must be greater than or equal to 1');
        }
    }
}
