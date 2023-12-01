<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice;

enum InvoiceStatus: int
{
    case ISSUED = 1;

    case PARTIALLY_PAID = 2;

    case PAID = 3;

    case OVERDUE = 99;
}
