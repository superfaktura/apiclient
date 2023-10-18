<?php declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\CashRegister;

use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\StatusCodeInterface;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;
use SuperFaktura\ApiClient\Contract\CashRegister\CannotGetAllCashRegistersException;

final readonly class CashRegisters implements Contract\CashRegister\CashRegisters
{
    public Items $items;

    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private string $base_uri,
        private string $authorization_header_value
    ) {
        $this->items = new Items(
            $this->http_client,
            $this->request_factory,
            $this->response_factory,
            $this->base_uri,
            $this->authorization_header_value,
        );
    }

    public function getAll(): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/cash_registers/getDetails',
            )->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory->createFromHttpResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (\JsonException|ClientExceptionInterface $e) {
            throw new CannotGetAllCashRegistersException($request, $e->getMessage(), $e->getCode(), $e);
        }

        return $response;
    }

    public function getById(int $id): Response
    {
        $request = $this->request_factory
            ->createRequest(
                RequestMethodInterface::METHOD_GET,
                $this->base_uri . '/cash_registers/view/' . $id,
            )->withHeader('Authorization', $this->authorization_header_value);

        try {
            $response = $this->response_factory->createFromHttpResponse(
                $this->http_client->sendRequest($request),
            );
        } catch (\JsonException|ClientExceptionInterface $e) {
            throw new Contract\CashRegister\CannotGetCashRegisterException($request, $e->getMessage(), $e->getCode(), $e);
        }

        if ($response->status_code === StatusCodeInterface::STATUS_NOT_FOUND) {
            throw new Contract\CashRegister\CashRegisterNotFoundException($request);
        }

        return $response;
    }
}
