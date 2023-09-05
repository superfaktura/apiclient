<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use SuperFaktura\ApiClient\UseCase\Client\Clients;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Version\ComposerProvider;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;
use SuperFaktura\ApiClient\Response\ResponseFactoryInterface;

final readonly class ApiClient
{
    public Contract\Clients $clients;

    public function __construct(
        private Authorization\Provider $authorization_provider,
        private string $base_uri,
        private ClientInterface $http_client = new Client(),
        private RequestFactoryInterface $request_factory = new HttpFactory(),
        private ResponseFactoryInterface $response_factory = new ResponseFactory(),
    ) {
        $authorization_header_value = (new Authorization\Header\Builder(new ComposerProvider()))
            ->build($this->authorization_provider->getAuthorization());

        $this->clients = new Clients(
            http_client: $this->http_client,
            request_factory: $this->request_factory,
            response_factory: $this->response_factory,
            query_params_convertor: new NamedParamsConvertor(),
            base_uri: $this->base_uri,
            authorization_header_value: $authorization_header_value,
        );
    }
}
