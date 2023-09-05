<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\UseCase\Client\ClientsQuery;
use SuperFaktura\ApiClient\UseCase\Client\CannotGetClientException;
use SuperFaktura\ApiClient\UseCase\Client\CannotGetAllClientsException;

interface Clients
{
    /**
     * @throws CannotGetClientException
     */
    public function getById(int $id): Response;

    /**
     * @throws CannotGetAllClientsException
     */
    public function getAll(ClientsQuery $query): Response;
}
