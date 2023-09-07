<?php

declare(strict_types=1);

namespace SuperFaktura\ApiClient\Test\UseCase\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\HttpFactory;
use SuperFaktura\ApiClient\Filter\Sort;
use Fig\Http\Message\StatusCodeInterface;
use SuperFaktura\ApiClient\Test\TestCase;
use PHPUnit\Framework\Attributes\UsesClass;
use Fig\Http\Message\RequestMethodInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use SuperFaktura\ApiClient\Filter\TimePeriod;
use PHPUnit\Framework\Attributes\DataProvider;
use SuperFaktura\ApiClient\Response\RateLimit;
use SuperFaktura\ApiClient\Filter\SortDirection;
use SuperFaktura\ApiClient\Filter\TimePeriodEnum;
use SuperFaktura\ApiClient\UseCase\Client\Clients;
use SuperFaktura\ApiClient\Request\RequestException;
use SuperFaktura\ApiClient\Response\ResponseFactory;
use SuperFaktura\ApiClient\Filter\NamedParamsConvertor;
use SuperFaktura\ApiClient\UseCase\Client\ClientsQuery;
use SuperFaktura\ApiClient\UseCase\Client\Contact\Contacts;
use SuperFaktura\ApiClient\UseCase\Client\CannotGetClientException;
use SuperFaktura\ApiClient\UseCase\Client\CannotGetAllClientsException;

#[CoversClass(Clients::class)]
#[CoversClass(\SuperFaktura\ApiClient\Response\Response::class)]
#[CoversClass(CannotGetClientException::class)]
#[CoversClass(CannotGetAllClientsException::class)]
#[UsesClass(RequestException::class)]
#[UsesClass(NamedParamsConvertor::class)]
#[UsesClass(RateLimit::class)]
#[UsesClass(ResponseFactory::class)]
#[UsesClass(ClientsQuery::class)]
#[UsesClass(Sort::class)]
#[UsesClass(Contacts::class)]
final class ClientsTest extends TestCase
{
    public function testGetClientByIdSuccessResponse(): void
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

    public function testGetAllSuccessResponse(): void
    {
        $fixture = __DIR__ . '/fixtures/clients.json';

        $response = $this->getClientsFacadeWithMockedHttpClient(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], $this->jsonFromFixture($fixture)),
            ),
        )
            ->getAll();

        self::assertSame($this->arrayFromFixture($fixture), $response->data);
    }

    public static function getAllClientsQueryProvider(): \Generator
    {
        $base_uri = '/clients/index.json/';

        yield 'no filter specified, default query parameters' => [
            'expected' => $base_uri . self::getQueryString(),
            'query' => new ClientsQuery(),
        ];

        yield 'filter by uuid' => [
            'expected' => $base_uri . self::getQueryString(['search_uuid' => 'd98ae494-76a7-47f0-b4c1-b77d91d8898a']),
            'query' => new ClientsQuery(uuid: 'd98ae494-76a7-47f0-b4c1-b77d91d8898a'),
        ];

        yield 'full text search' => [
            'expected' => $base_uri . self::getQueryString(['search' => 'SuperFaktúra']),
            'query' => new ClientsQuery(full_text: 'SuperFaktúra'),
        ];

        yield 'filter by first character' => [
            'expected' => $base_uri . self::getQueryString(['char_filter' => 'S']),
            'query' => new ClientsQuery(first_letter: 'S'),
        ];

        yield 'filter by tag id' => [
            'expected' => $base_uri . self::getQueryString(['tag' => 1]),
            'query' => new ClientsQuery(tag: 1),
        ];

        yield 'filter by created date since to range' => [
            'expected' => $base_uri . self::getQueryString([
                'created' => TimePeriodEnum::SINCE_TO->value,
                'created_since' => '2023-01-02T01:02:03+00:00',
                'created_to' => '2023-02-03T04:05:06+00:00',
            ]),
            'query' => new ClientsQuery(
                created: new TimePeriod(
                    period: TimePeriodEnum::SINCE_TO,
                    from: new \DateTimeImmutable('2023-01-02 01:02:03'),
                    to: new \DateTimeImmutable('2023-02-03 04:05:06'),
                ),
            ),
        ];

        yield 'filter by modified date since to range' => [
            'expected' => $base_uri . self::getQueryString([
                'modified' => TimePeriodEnum::SINCE_TO->value,
                'modified_since' => '2023-01-02T01:02:03+00:00',
                'modified_to' => '2023-02-03T04:05:06+00:00',
            ]),
            'query' => new ClientsQuery(
                modified: new TimePeriod(
                    period: TimePeriodEnum::SINCE_TO,
                    from: new \DateTimeImmutable('2023-01-02 01:02:03'),
                    to: new \DateTimeImmutable('2023-02-03 04:05:06'),
                ),
            ),
        ];

        yield 'pagination' => [
            'expected' => $base_uri . self::getQueryString(['page' => 2, 'per_page' => 50]),
            'query' => new ClientsQuery(page: 2, items_per_page: 50),
        ];

        yield 'sort' => [
            'expected' => $base_uri . self::getQueryString(
                ['sort' => 'name', 'direction' => SortDirection::DESC->value],
            ),
            'query' => new ClientsQuery(sort: new Sort(attribute: 'name', direction: SortDirection::DESC)),
        ];
    }

    #[DataProvider('getAllClientsQueryProvider')]
    public function testGetAllClientsQuery(string $expected, ClientsQuery $query): void
    {
        $http_client = $this->createMock(Client::class);
        $http_client
            ->expects(self::once())
            ->method('sendRequest')
            ->with(
                new Request(
                    method: RequestMethodInterface::METHOD_GET,
                    uri: $expected,
                    headers: ['Authorization' => ''],
                ),
            )
            ->willReturn($this->getHttpOkResponse());

        $this->getClientsFacadeWithMockedHttpClient($http_client)->getAll($query);
    }

    public function testGetAllRequestFailed(): void
    {
        $this->expectException(CannotGetAllClientsException::class);

        $this->getClientsFacadeWithMockedHttpClient(
            $this->getHttpClientWithMockRequestException(),
        )
            ->getAll();
    }

    public function testGetAllResponseDecodeFailed(): void
    {
        $this->expectException(CannotGetAllClientsException::class);

        $this->getClientsFacadeWithMockedHttpClient(
            $this->getHttpClientWithMockResponse(
                new Response(StatusCodeInterface::STATUS_OK, [], '{"items":'),
            ),
        )
            ->getAll();
    }

    private function getClientsFacadeWithMockedHttpClient(Client $client): Clients
    {
        return new Clients(
            http_client: $client,
            request_factory: new HttpFactory(),
            response_factory: new ResponseFactory(),
            query_params_convertor: new NamedParamsConvertor(),
            // no real requests are made during testing
            base_uri: '',
            authorization_header_value: '',
        );
    }

    /**
     * @param array<string, string|int|float|bool> $params
     */
    private static function getQueryString(array $params = []): string
    {
        $default_query_params = [
            'listinfo' => 1,
            'page' => 1,
            'per_page' => 100,
            'sort' => 'id',
            'direction' => SortDirection::ASC->value,
        ];

        return (new NamedParamsConvertor())->convert(
            array_merge($default_query_params, $params),
        );
    }
}
