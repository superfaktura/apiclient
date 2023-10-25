<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Expense;

enum ExpenseType: string
{
    case BILL = 'bill';

    case CONTRIBUTION = 'contribution';

    case INTERNAL = 'internal';

    case INVOICE = 'invoice';

    case NONDEDUCTIBLE = 'nondeductible';

    case RECEIVED_CREDIT_NOTE = 'recieved_credit_note';
}
