<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice\Payment;

enum PaymentType: string
{
    case ACCREDITATION = 'accreditation';

    case BARION = 'barion';

    case BESTERON = 'besteron';

    case CASH = 'cash';

    case CARD = 'card';

    case COD = 'cod';

    case CREDIT = 'credit';

    case DEBIT = 'debit';

    case ENCASHMENT = 'inkaso';

    case GOPAY = 'gopay';

    case OTHER = 'other';

    case PAYPAL = 'paypal';

    case POSTAL_ORDER = 'postal_order';

    case TRANSFER = 'transfer';

    case TRUSTPAY = 'trustpay';

    case VIAMO = 'viamo';
}
