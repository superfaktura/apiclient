<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Client;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\UseCase\Client\ClientsQuery;

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