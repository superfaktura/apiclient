<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\CashRegister;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;

interface Items
{
    /**
     * @param array<string, mixed> $data
     *
     * @throws CannotCreateCashRegisterItemException
     * @throws CannotCreateRequestException
     */
    public function create(int $cash_register_id, array $data): Response;
}
