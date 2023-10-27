<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Expense\Payment;

use SuperFaktura\ApiClient\Contract\PaymentType;
use SuperFaktura\ApiClient\UseCase\Money\Currency;

final class Payment
{
    /**
     * @param Currency|null $currency default value: home currency
     * @param PaymentType|null $payment_type default value: transfer
     * @param \DateTimeImmutable|null $payment_date default value: current date
     */
    public function __construct(
        public float $amount,
        public ?Currency $currency = null,
        public ?PaymentType $payment_type = null,
        public ?\DateTimeImmutable $payment_date = null,
    ) {
    }
}
