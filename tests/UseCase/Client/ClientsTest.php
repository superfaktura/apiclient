<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\UseCase\Client\Clients;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\UseCase\Client\CannotGetClientException;

#[CoversClass(Clients::class)]
#[CoversClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[CoversClass(CannotGetClientException::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
final class ClientsTest extends TestCase
{
    public function testGetClientById(): void
    {
        $fixture = __DIR__ . '/fixtures/client.json';

        $response = $this->getClientsFacadeWithMockedHttpClient(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getById(1);

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public function testGetClientByIdNotFound(): void
    {
        $this->expectException(CannotGetClientException::class);

        $fixture = __DIR__ . '/fixtures/not-found.json';

        $this->getClientsFacadeWithMockedHttpClient(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getById(1);
    }

    public function testGetClientByIdRequestFailed(): void
    {
        $this->expectException(CannotGetClientException::class);

        $this->getClientsFacadeWithMockedHttpClient(
            $this->getHttpClientWithMockRequestException(),
        )
            ->getById(1);
    }

    public function testGetClientByIdResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetClientException::class);

        $this->getClientsFacadeWithMockedHttpClient(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{"Client":'),
            ),
        )
            ->getById(1);
    }

    private function getClientsFacadeWithMockedHttpClient(Client $client): Clients
    {
        return new Clients(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            // no real requests are made during testing
            base_uri: '',
            authorization_header_value: '',
        );
    }
}
