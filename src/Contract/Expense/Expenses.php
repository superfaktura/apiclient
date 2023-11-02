<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Expense;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\UseCase\Expense\ExpensesQuery;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;

interface Expenses
{
    /**
     * @throws CannotGetAllExpensesException
     */
    public function getAll(ExpensesQuery $query = new ExpensesQuery()): Response;

    /**
     * @throws CannotGetAllCategoriesException
     */
    public function getAllCategories(): Response;

    /**
     * @throws CannotGetExpenseException
     * @throws ExpenseNotFoundException
     */
    public function getById(int $id): Response;

    /**
     * @param array<string, mixed> $expense
     * @param array<array<string, mixed>> $items
     * @param array<string, mixed> $client
     * @param array<string, mixed> $extra
     * @param array<string, mixed> $my_data
     * @param int[] $tags
     *
     * @throws CannotCreateExpenseException
     * @throws CannotCreateRequestException
     */
    public function create(
        array $expense,
        array $items = [],
        array $client = [],
        array $extra = [],
        array $my_data = [],
        array $tags = [],
    ): Response;

    /**
     * @param array<string, mixed> $expense
     * @param array<array<string, mixed>> $items
     * @param array<string, mixed> $client
     * @param array<string, mixed> $extra
     * @param array<string, mixed> $my_data
     * @param int[] $tags
     *
     * @throws CannotUpdateExpenseException
     * @throws CannotCreateRequestException
     * @throws ExpenseNotFoundException
     */
    public function update(
        int $id,
        array $expense = [],
        array $items = [],
        array $client = [],
        array $extra = [],
        array $my_data = [],
        array $tags = [],
    ): Response;

    /**
     * @throws CannotDeleteExpenseException
     * @throws ExpenseNotFoundException
     */
    public function delete(int $id): void;
}
