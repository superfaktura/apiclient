<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Expense;

enum ExpenseStatus: int
{
    case NEW = 1;

    case PARTIALLY_PAID = 2;

    case PAID = 3;

    case OVERDUE = 99;
}
