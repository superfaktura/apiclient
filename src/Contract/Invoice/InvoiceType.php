<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice;

enum InvoiceType: string
{
    case REGULAR = 'regular';
    case PROFORMA = 'proforma';
    case CANCEL = 'cancel';
    case DELIVERY = 'delivery';
    case DRAFT = 'draft';
    case ESTIMATE = 'estimate';
    case ORDER = 'order';
    case REVERSE_ORDER = 'reverse_order';
}
