<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Filter;

enum SortDirection: string
{
    case ASC = 'ASC';

    case DESC = 'DESC';
}
