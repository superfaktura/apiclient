<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Filter;

final class Sort
{
    public function __construct(
        public string $attribute,
        public SortDirection $direction,
    ) {
    }
}
