<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Contract\Client\Contact;

use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Client\ClientNotFoundException;

interface Contacts
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
     * @throws ClientNotFoundException
     */
    public function create(int $client_id, array $contact): Response;

    /**
     * @throws CannotDeleteContactException
     * @throws ContactNotFoundException
     */
    public function delete(int $contact_id): void;
}
