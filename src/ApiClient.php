<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\UseCase\Client\Clients;
use SuperFaktura\ApiClient\Version\ComposerProvider;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;

final readonly class ApiClient
{
    public Contract\Clients $clients;

    public function __construct(
        private ClientInterface $http_client,
        private RequestFactoryInterface $request_factory,
        private ResponseFactoryInterface $response_factory,
        private Authorization\Provider $authorization_provider,
        private string $base_uri,
    ) {
        $authorization_header_value = (new Authorization\Header\Builder(new ComposerProvider()))
            ->build($this->authorization_provider->getAuthorization());

        $this->clients = new Clients(
            $this->http_client,
            $this->request_factory,
            $this->response_factory,
            $this->base_uri,
            $authorization_header_value,
        );
    }
}
