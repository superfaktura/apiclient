<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice\Export;

enum DocumentSort: string
{
    case CLIENT = 'client';
    case DATE = 'date';
}
