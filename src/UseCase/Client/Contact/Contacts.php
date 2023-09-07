<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Client\Contact;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Contract\ClientContacts;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;

final readonly class Contacts implements ClientContacts
{
    private const CONTACT = 'ContactPerson';

    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value
    ) {
    }

    public function getAllByClientId(int $client_id): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/contact_people/getContactPeople/' . $client_id,
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory->createFromHttpResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetAllContactsException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($this->isClientNotFoundResponse($response)) {
            throw new ClientNotFoundException($request);
        }

        return $response;
    }

    public function create(int $client_id, array $contact): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_POST,
                $this->base_uri . '/contact_people/add/api%3A1',
            )
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody(
                Utils::streamFor('data=' . $this->transformContactDataToJson($client_id, $contact)),
            );

        try {
            $response = $this->response_factory->createFromHttpResponse(
                $this->http_client->sendRequest($request),
            );

            if ($response->isError()) {
                throw new CannotCreateContactException($request, $response->data['message'] ?? '');
            }

            return $response;
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotCreateContactException($request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function deleteById(int $contact_id): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/contact_people/delete/' . $contact_id,
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromHttpResponse($this->http_client->sendRequest($request));

            if ($response->isError()) {
                throw new CannotDeleteContactException($request, $response->data['error_message'] ?? '');
            }
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotDeleteContactException($request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    private function isClientNotFoundResponse(Response $response): bool
    {
        return $response->data === [
            [self::CONTACT => ['client' => true]],
        ];
    }

    /**
     * @param array<string, mixed> $contact
     *
     * @throws CannotCreateRequestException
     */
    private function transformContactDataToJson(int $client_id, array $contact): string
    {
        try {
            return json_encode(
                [self::CONTACT => ['client_id' => $client_id, ...$contact]],
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
