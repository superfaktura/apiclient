<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Client;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\StatusCodeInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Filter\QueryParamsConvertor;
use SuperFaktura\ApiClient\UseCase\Client\Contact\Contacts;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Client\ClientNotFoundException;
use SuperFaktura\ApiClient\Contract\Client\CannotGetClientException;
use SuperFaktura\ApiClient\Contract\Client\CannotGetAllClientsException;
use SuperFaktura\ApiClient\Test\UseCase\Client\CannotCreateClientException;

final readonly class Clients implements Contract\Client\Clients
{
    public Contacts $contacts;

    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private QueryParamsConvertor $query_params_convertor,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
        $this->contacts = new Contacts(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            base_uri: $this->base_uri,
            authorization_header_value: $this->authorization_header_value,
        );
    }

    public function getById(int $id): Response
    {
        $request = $this->request_factory
            ->createRequest(RequestMethodInterface::METHOD_GET, $this->base_uri . '/clients/view/' . $id)
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromHttpResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetClientException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new ClientNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotGetClientException($request, $response->data['error_message'] ?? '');
        }

        return $response;
    }

    public function getAll(ClientsQuery $query = new ClientsQuery()): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/clients/index.json/' . $this->getClientsQueryString($query),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            return $this->response_factory
                ->createFromHttpResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetAllClientsException($request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    private function getClientsQueryString(ClientsQuery $query): string
    {
        return $this->query_params_convertor->convert([
            'listinfo' => 1,
            'page' => $query->page,
            'per_page' => $query->items_per_page,
            'sort' => $query->sort->attribute,
            'direction' => $query->sort->direction->value,
            'search_uuid' => $query->uuid,
            'search' => $query->full_text,
            'char_filter' => $query->first_letter,
            'tag' => $query->tag,
            'created' => $query->created?->period->value,
            'created_since' => $query->created?->from?->format('c'),
            'created_to' => $query->created?->to?->format('c'),
            'modified' => $query->modified?->period->value,
            'modified_since' => $query->modified?->from?->format('c'),
            'modified_to' => $query->modified?->to?->format('c'),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws CannotCreateClientException
     */
    public function create(array $data): Response
    {
        $request = $this->request_factory
            ->createRequest(RequestMethodInterface::METHOD_POST, $this->base_uri . '/clients/create')
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($this->transformClientDataToJson($data)));

        try {
            $response = $this->response_factory->createFromHttpResponse($this->http_client->sendRequest($request));
        } catch (\JsonException|ClientExceptionInterface $e) {
            throw new CannotCreateClientException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotCreateClientException($request, $response->data['message'] ?? '');
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function transformClientDataToJson(array $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
