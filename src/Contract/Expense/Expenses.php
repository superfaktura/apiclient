<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Expense;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\UseCase\Expense\ExpensesQuery;

interface Expenses
{
    /**
     * @throws CannotGetAllExpensesException
     */
    public function getAll(ExpensesQuery $query = new ExpensesQuery()): Response;
}
