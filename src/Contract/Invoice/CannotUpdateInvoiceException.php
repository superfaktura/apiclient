<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Invoice;

use Psr\Http\Message\RequestInterface;
use SuperFaktura\ApiClient\Request\RequestException;

final class CannotUpdateInvoiceException extends RequestException
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        RequestInterface $request,
        private readonly array $errors,
        string $message = 'Cannot update invoice',
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($request, $message, $code, $previous);
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
