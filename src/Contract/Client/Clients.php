<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Client;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\UseCase\Client\ClientsQuery;

interface Clients
{
    /**
     * @throws CannotGetClientException
     * @throws ClientNotFoundException
     */
    public function getById(int $id): Response;

    /**
     * @throws CannotGetAllClientsException
     */
    public function getAll(ClientsQuery $query): Response;

    /**
     * @param array<string, array<string, mixed>> $data
     *
     * @throws ClientNotFoundException
     * @throws CannotUpdateClientException
     */
    public function update(int $id, array $data): Response;

    /**
     * @throws ClientNotFoundException
     * @throws CannotDeleteClientException
     */
    public function delete(int $id): Response;

    /**
     * @param array<string, mixed> $data
     *
     * @throws CannotCreateClientException
     */
    public function create(array $data): Response;
}
