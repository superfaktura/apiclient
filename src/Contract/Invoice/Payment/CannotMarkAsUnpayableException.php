<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice\Payment;

use SuperFaktura\ApiClient\Request\RequestException;

final class CannotMarkAsUnpayableException extends RequestException
{
}
