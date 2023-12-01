<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\BankAccount;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;

interface BankAccounts
{
    /**
     * @throws CannotGetAllBankAccountsException
     */
    public function getAll(): Response;

    /**
     * @param array<string, mixed> $bank_account
     *
     * @throws CannotCreateBankAccountException
     * @throws CannotCreateRequestException
     */
    public function create(array $bank_account): Response;

    /**
     * @param array<string, mixed> $bank_account
     *
     * @throws CannotUpdateBankAccountException
     * @throws CannotCreateRequestException
     */
    public function update(int $id, array $bank_account): Response;

    /**
     * @throws CannotDeleteBankAccountException
     * @throws BankAccountNotFoundException
     */
    public function delete(int $bank_account_id): void;
}
