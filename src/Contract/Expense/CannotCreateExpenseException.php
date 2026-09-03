<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Expense;

use Psr\Http\Message\RequestInterface;
use SuperFaktura\ApiClient\Request\RequestException;

final class CannotCreateExpenseException extends RequestException
{
    /**
     * @param array<string|int, string|string[]> $errors
     */
    public function __construct(
        RequestInterface $request,
        private readonly array $errors = [],
        string $message = 'Cannot create expense',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($request, $message, $code, $previous);
    }

    /**
     * @return array<string|int, string|string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
