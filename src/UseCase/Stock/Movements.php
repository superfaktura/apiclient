<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Stock;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract\Stock;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Stock\CannotCreateMovementException;

final readonly class Movements implements Stock\Movements
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function create(int $item_id, array $data): Response
    {
        $data['StockLog'] = array_map(
            static fn ($movement) => array_merge($movement, ['stock_item_id' => $item_id]),
            $data['StockLog'],
        );

        return $this->createAndGetResponse($data);
    }

    public function createWithSku(string $sku, array $data): Response
    {
        $data['StockLog'] = array_map(
            static fn ($movement) => array_merge($movement, ['sku' => $sku]),
            $data['StockLog'],
        );

        return $this->createAndGetResponse($data);
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
}
