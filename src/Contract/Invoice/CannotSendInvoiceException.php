<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice;

use Psr\Http\Message\RequestInterface;
use SuperFaktura\ApiClient\Request\RequestException;

final class CannotSendInvoiceException extends RequestException
{
    /**
     * @param array<string|int, mixed> $errorResponseData decoded body of the API error response, empty when no response was received
     */
    public function __construct(
        RequestInterface $request,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly array $errorResponseData = [],
    ) {
        parent::__construct($request, $message, $code, $previous);
    }

    /**
     * @return array<string|int, mixed>
     */
    public function getErrorResponseData(): array
    {
        return $this->errorResponseData;
    }
}
