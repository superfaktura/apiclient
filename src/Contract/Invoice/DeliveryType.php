<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice;

enum DeliveryType: string
{
    case COURIER = 'courier';
    case FREIGHT = 'haulage';
    case MAIL = 'mail';
    case PERSONAL = 'personal';
    case PICKUP_POINT = 'pickup_point';
}
