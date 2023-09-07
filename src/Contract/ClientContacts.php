<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\UseCase\Client\Contact\ClientNotFoundException;
use SuperFaktura\ApiClient\UseCase\Client\Contact\CannotCreateContactException;
use SuperFaktura\ApiClient\UseCase\Client\Contact\CannotDeleteContactException;
use SuperFaktura\ApiClient\UseCase\Client\Contact\CannotGetAllContactsException;

interface ClientContacts
{
    /**
     * @throws CannotGetAllContactsException
     * @throws ClientNotFoundException
     */
    public function getAllByClientId(int $client_id): Response;

    /**
     * @param array<string, mixed> $contact
     *
     * @throws CannotCreateContactException
     * @throws CannotCreateRequestException
     */
    public function create(int $client_id, array $contact): Response;

    /**
     * @throws CannotDeleteContactException
     */
    public function deleteById(int $contact_id): void;
}
