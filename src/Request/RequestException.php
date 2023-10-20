<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\Request;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Client\RequestExceptionInterface;

abstract class RequestException extends \RuntimeException implements RequestExceptionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        string $message = '',
        int $code = 0,
        \Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
