<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Client;

use SuperFaktura\ApiClient\Filter\Sort;
use SuperFaktura\ApiClient\Filter\TimePeriod;
use SuperFaktura\ApiClient\Filter\SortDirection;

final class ClientsQuery
{
    private const ITEMS_PER_PAGE_MAX = 100;

    public function __construct(
        public ?string $uuid = null,
        public ?string $full_text = null,
        public ?string $first_letter = null,
        public ?int $tag = null,
        public ?TimePeriod $created = null,
        public ?TimePeriod $modified = null,
        public Sort $sort = new Sort('id', SortDirection::ASC),
        public int $page = 1,
        public int $items_per_page = self::ITEMS_PER_PAGE_MAX,
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
