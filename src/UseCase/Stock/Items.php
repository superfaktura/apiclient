<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Stock;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use Fig\Http\Message\StatusCodeInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Contract\Stock\ItemsQuery;
use SuperFaktura\ApiClient\Filter\QueryParamsConvertor;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Contract\Stock\ItemNotFoundException;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Stock\CannotCreateItemException;
use SuperFaktura\ApiClient\Contract\Stock\CannotDeleteItemException;
use SuperFaktura\ApiClient\Contract\Stock\CannotUpdateItemException;
use SuperFaktura\ApiClient\Contract\Stock\CannotGetAllItemsException;
use SuperFaktura\ApiClient\Contract\Stock\CannotGetItemByIdException;

final readonly class Items implements \SuperFaktura\ApiClient\Contract\Stock\Items
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

    public function create(array $data): Response
    {
        $request = $this->request_factory
            ->createRequest(RequestMethodInterface::METHOD_POST, $this->base_uri . '/stock_items/add')
            ->withBody(Utils::streamFor($this->transformDataToJson($data)))
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
        ;

        try {
            $response = $this->response_factory->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (\JsonException|ClientExceptionInterface $e) {
            throw new CannotCreateItemException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotCreateItemException($request, $response->data['message'] ?? '');
        }

        return $response;
    }

    public function getById(int $id): Response
    {
        $request = $this->request_factory
            ->createRequest(RequestMethodInterface::METHOD_GET, $this->base_uri . '/stock_items/view/' . $id)
            ->withHeader('Authorization', $this->authorization_header_value)
        ;

        try {
            $response = $this->response_factory->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (\JsonException|ClientExceptionInterface $e) {
            throw new CannotGetItemByIdException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new ItemNotFoundException($request);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function transformDataToJson(array $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function getAll(ItemsQuery $query = new ItemsQuery()): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/stock_items/index.json/' . $this->getParamsOf($query),
            )->withHeader('Authorization', $this->authorization_header_value)
        ;

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));

            if ($response->isError()) {
                throw new CannotGetAllItemsException($request, $response->data['message'] ?? '');
            }

            return $response;
        } catch (\JsonException|ClientExceptionInterface $e) {
            throw new CannotGetAllItemsException($request, $e->getMessage(), $e->getCode(), $e);
        }
    }

    public function delete(int $id): void
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_DELETE,
                $this->base_uri . '/stock_items/delete/' . $id,
            )->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory
                ->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (\JsonException|ClientExceptionInterface $e) {
            throw new CannotDeleteItemException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new ItemNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotDeleteItemException($request, $response->data['message'] ?? '');
        }
    }

    private function getParamsOf(ItemsQuery $query): string
    {
        return $this->query_params_convertor->convert([
            'listinfo' => 1,
            'page' => $query->page,
            'per_page' => $query->per_page,
            'sort' => $query->sort?->attribute,
            'direction' => $query->sort?->direction->value,
            'price_from' => $query->price_from,
            'price_to' => $query->price_to,
            'search' => $query->search !== null ? base64_encode($query->search) : null,
            'sku' => $query->sku !== null ? base64_encode($query->sku) : null,
            'status' => $query->status,
        ]);
    }

    public function update(int $id, array $data): Response
    {
        $request = $this->request_factory
            ->createRequest(RequestMethodInterface::METHOD_PATCH, $this->base_uri . '/stock_items/edit/' . $id)
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($this->transformDataToJson($data)));

        try {
            $response = $this->response_factory->createFromJsonResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (\JsonException|ClientExceptionInterface $e) {
            throw new CannotUpdateItemException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new ItemNotFoundException($request);
        }

        if ($response->isError()) {
            throw new CannotUpdateItemException($request, $response->data['message'] ?? '');
        }

        return $response;
    }
}
