<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Expense\Payment;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\UseCase\Expense\Payment\Payment;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;

interface Payments
{
    /**
     * @throws CannotPayExpenseException
     * @throws CannotCreateRequestException
     */
    public function create(int $id, Payment $payment): Response;

    /**
     * @throws CannotDeleteExpensePaymentException
     */
    public function delete(int $id): void;
}
