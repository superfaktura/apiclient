<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Invoice\Payment;

use SuperFaktura\ApiClient\Contract\PaymentType;
use SuperFaktura\ApiClient\UseCase\Money\Currency;

final class Payment
{
    /**
     * @param float|null $amount default value: invoice total price
     * @param Currency|null $currency default value: by market
     * @param PaymentType|null $payment_type default value: transfer
     * @param \DateTimeImmutable|null $payment_date default value: current date
     */
    public function __construct(
        public ?float $amount = null,
        public ?Currency $currency = null,
        public ?PaymentType $payment_type = null,
        public ?string $document_number = null,
        public ?int $cash_register_id = null,
        public ?\DateTimeImmutable $payment_date = null,
    ) {
    }
}
