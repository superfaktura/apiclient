<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\UseCase\Client\CannotGetClientException;

interface Clients
{
    /**
     * @throws CannotGetClientException
     */
    public function getById(int $id): Response;
}
