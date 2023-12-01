<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\CashRegister;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Client\ClientInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Contract\CashRegister;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;

final readonly class Items implements CashRegister\Items
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value,
    ) {
    }

    public function create(int $cash_register_id, array $data): Response
    {
        $request = $this->request_factory->createRequest(
            RequestMethodInterface::METHOD_POST,
            $this->base_uri . '/cash_register_items/add',
        )->withHeader('Authorization', $this->authorization_header_value)
            ->withHeader('Content-Type', 'application/json')
            ->withBody(Utils::streamFor($this->jsonFrom($cash_register_id, $data)));

        try {
            $http_response = $this->http_client->sendRequest($request);
            $response = $this->response_factory->createFromJsonResponse($http_response);
        } catch (ClientExceptionInterface|\JsonException $e) {
            throw new CashRegister\CannotCreateCashRegisterItemException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->isError()) {
            throw new CashRegister\CannotCreateCashRegisterItemException($request, $response->data['message'] ?? '');
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws \JsonException
     */
    private function jsonFrom(int $cash_register_id, array $data): string
    {
        return json_encode(['CashRegisterItem' => [...$data, 'cash_register_id' => $cash_register_id]], JSON_THROW_ON_ERROR);
    }
}
