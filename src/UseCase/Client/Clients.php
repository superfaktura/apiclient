<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\UseCase\Client;

use Psr\Http\Client\ClientInterface;
use SuperFaktura\ApiClient\Contract;
use Fig\Http\Message\RequestMethodInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\Response\Response;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;

final readonly class Clients implements Contract\Clients
{
    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
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
}
