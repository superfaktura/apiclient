<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\CashRegister;

use SuperFaktura\ApiClient\Response\Response;

interface CashRegisters
{
    /**
     * @throws CannotGetAllCashRegistersException
     */
    public function getAll(): Response;

    /**
     * @throws CannotGetCashRegisterException
     * @throws CashRegisterNotFoundException
     */
    public function getById(int $id): Response;
}
