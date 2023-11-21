<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Stock;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract\Stock;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Filter\QueryParamsConvertor;
use SuperFaktura\ApiClient\Contract\Stock\MovementsQuery;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Stock\CannotCreateMovementException;
use SuperFaktura\ApiClient\Contract\Stock\CannotGetAllMovementsException;

final readonly class Movements implements Stock\Movements
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

    public function create(int $item_id, array $data): Response
    {
        $request_data = [
            'StockLog' => array_map(
                static fn ($movement) => array_merge($movement, ['stock_item_id' => $item_id]),
                $data,
            ),
        ];

        return $this->createAndGetResponse($request_data);
    }

    public function createWithSku(string $sku, array $data): Response
    {
        $request_data = [
            'StockLog' => array_map(
                static fn ($movement) => array_merge($movement, ['sku' => $sku]),
                $data,
            ),
        ];

        return $this->createAndGetResponse($request_data);
    }

    /**
     * @param array{StockLog: array<string, mixed>[]} $data
     */
    private function transformDataToJson(array $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new CannotCreateRequestException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array{StockLog: array<string, mixed>[]} $data
     */
    private function createAndGetResponse(array $data): Response
    {
        $request = $this->request_factory
            ->createRequest(RequestMethodInterface::METHOD_POST, $this->base_uri . '/stock_items/addStockMovement')
            ->withBody(Utils::streamFor($this->transformDataToJson($data)))
            ->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json');

        try {
            $response = $this->response_factory->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CannotCreateMovementException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotCreateMovementException($request, $response->data['message'] ?? '');
        }

        return $response;
    }

    public function getAll(int $id, MovementsQuery $query = new MovementsQuery()): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/stock_items/movements/' . $id . '/' . $this->getParamsOf($query),
            )
            ->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory->createFromJsonResponse($this->http_client->sendRequest($request));
        } catch (\JsonException|ClientExceptionInterface $e) {
            throw new CannotGetAllMovementsException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotGetAllMovementsException($request, $response->data['message'] ?? '');
        }

        return $response;
    }

    private function getParamsOf(MovementsQuery $query): string
    {
        return $this->query_params_convertor->convert([
            'sort' => $query->sort?->attribute,
            'direction' => $query->sort?->direction->value,
            'page' => $query->page,
            'per_page' => $query->per_page,
        ]);
    }
}
