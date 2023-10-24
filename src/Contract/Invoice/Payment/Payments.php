<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice\Payment;

use SuperFaktura\ApiClient\UseCase\Invoice\Payment\Payment;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Invoice\InvoiceNotFoundException;

interface Payments
{
    /**
     * @throws InvoiceNotFoundException
     * @throws CannotMarkAsUnpayableException
     */
    public function markAsUnPayable(int $invoice_id): void;

    /**
     * @throws CannotPayInvoiceException
     * @throws CannotCreateRequestException
     */
    public function pay(int $id, Payment $payment = new Payment()): void;

    /**
     * @throws CannotDeleteInvoicePaymentException
     */
    public function delete(int $id): void;
}
