<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Stock;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Request\CannotCreateRequestException;
use SuperFaktura\ApiClient\Contract\Stock\CannotCreateItemException;

final readonly class Items implements \SuperFaktura\ApiClient\Contract\Stock\Items
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
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
            $response = $this->response_factory->createFromHttpResponse($this->http_client->sendRequest($request));
        } catch (\JsonException|ClientExceptionInterface $e) {
            throw new CannotCreateItemException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CannotCreateItemException($request, $response->data['message'] ?? '');
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
}
