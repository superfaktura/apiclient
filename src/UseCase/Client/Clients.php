<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Client;

use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Filter\QueryParamsConvertor;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;

final readonly class Clients implements Contract\Clients
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private QueryParamsConvertor $query_params_convertor,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function getById(int $id): Response
    {
        $request = $this->request_factory
            ->createRequest(RequestMethodInterface::METHOD_GET, $this->base_uri . '/clients/view/' . $id)
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromHttpResponse($this->http_client->sendRequest($request));

            if ($response->isError()) {
                throw new CannotGetClientException($request, $response->data['error_message'] ?? '');
            }

            return $response;
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotGetClientException($request, $e->getMessage(), $e->getCode(), $e);
        }
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
}
