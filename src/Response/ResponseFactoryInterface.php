<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Response;

use Psr\Http\Message\ResponseInterface;

interface ResponseFactoryInterface
{
    /**
     * @throws \JsonException
     * @throws \UnexpectedValueException
     */
    public function createFromHttpResponse(ResponseInterface $response): Response;
}
