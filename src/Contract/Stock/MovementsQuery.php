<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Stock;

use SuperFaktura\ApiClient\Filter\Sort;

final readonly class MovementsQuery
{
    private const PER_PAGE_MAX = 200;

    public function __construct(
        public ?Sort $sort = null,
        public ?int $page = null,
        public ?int $per_page = null,
    ) {
        if ($this->page !== null && $this->page < 1) {
            throw new \InvalidArgumentException('Page argument must be greater than or equal to 1');
        }

        if ($this->per_page !== null && ($this->per_page < 1 || $this->per_page > self::PER_PAGE_MAX)) {
            throw new \InvalidArgumentException(sprintf(
                'Items per page argument must be greater than or equal to 1 and less than %d',
                self::PER_PAGE_MAX,
            ));
        }
    }
}
